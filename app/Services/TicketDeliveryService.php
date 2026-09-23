<?php

namespace App\Services;

use App\Models\EventInvitation;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TicketDeliveryService
{
    public function __construct(
        private TicketQrService $qr,
        private TelesomSmsService $sms,
        private WhatsAppCloudService $whatsapp,
        private PushNotificationService $push,
    ) {}

    public function sendForOrder(Order $order): void
    {
        $order->load(['items.tickets', 'event']);

        $tickets = $order->items->flatMap->tickets->where('status', '!=', 'cancelled')->values();
        if ($tickets->isEmpty()) {
            return;
        }

        $this->sendEmail($order, $tickets);
        $this->sendPaidNotificationSms($order, $tickets);
        $this->sendOrderPush($order, $tickets);
    }

    public function sendForInvitation(EventInvitation $invitation, ?string $channel = null): void
    {
        $invitation->loadMissing(['tickets', 'event', 'ticketType']);

        if (! $invitation->isActive()) {
            return;
        }

        $channel = in_array($channel, ['sms', 'whatsapp'], true)
            ? $channel
            : (in_array($invitation->delivery_channel, ['sms', 'whatsapp'], true)
                ? $invitation->delivery_channel
                : null);

        $inviteUrl = $invitation->publicUrl();
        $eventTitle = $invitation->event?->title ?? 'xafladdaada';
        $qty = $invitation->tickets->where('status', '!=', 'cancelled')->count() ?: $invitation->quantity;
        $guestName = $invitation->guest_name ?: 'Marti';

        $codes = $invitation->tickets
            ->where('status', '!=', 'cancelled')
            ->take(3)
            ->map(fn (Ticket $t) => $t->ticket_code)
            ->implode(', ');

        $body = "Ekaadh: Salaam {$guestName}, waxaa lagugu casuumay munaasabada {$eventTitle}. "
            ."{$qty} tigidh. Fur casuumaddaada: {$inviteUrl}.";

        if ($codes !== '') {
            $body .= " Koodhadhka: {$codes}";
            if ($invitation->tickets->where('status', '!=', 'cancelled')->count() > 3) {
                $body .= '…';
            }
        }

        $sendSms = $channel === null || $channel === 'sms';
        $sendWhatsApp = $channel === null || $channel === 'whatsapp';

        $smsStatus = $sendSms
            ? $this->deliverSms($invitation->guest_phone, $body)
            : 'skipped';
        $waStatus = $sendWhatsApp
            ? $this->deliverInviteWhatsApp(
                $invitation->guest_phone,
                $guestName,
                $eventTitle,
                (int) $qty,
                $inviteUrl,
            )
            : 'skipped';

        $invitation->update([
            'sms_status' => $smsStatus,
            'whatsapp_status' => $waStatus,
            'delivery_channel' => $channel ?? $invitation->delivery_channel,
            'last_sent_at' => now(),
        ]);

        $this->push->sendToPhone(
            $invitation->guest_phone,
            'Waa lagugu casuumay',
            "Waxaa lagugu casuumay munaasabada {$eventTitle}. Fur Ekaadh si aad u aragto casuumaddaada.",
            PushNotificationService::TYPE_INVITATION_RECEIVED,
            [
                'invitation_id' => (string) $invitation->id,
                'event_id' => (string) ($invitation->event_id ?? ''),
                'token' => (string) $invitation->token,
            ],
            true,
        );

        $failedLabel = null;
        if ($sendSms && $smsStatus === 'failed') {
            $failedLabel = 'SMS';
        } elseif ($sendWhatsApp && $waStatus === 'failed') {
            $failedLabel = 'WhatsApp';
        }

        if ($failedLabel) {
            $event = $invitation->event?->loadMissing(['owner', 'organizer.user']);
            $notifyUser = $event?->owner
                ?: $event?->organizer?->user;
            if ($notifyUser) {
                $guest = $invitation->guest_name ?: $invitation->guest_phone ?: 'a guest';
                $url = $event?->organizer_id
                    ? route('organizer.events.invitations.index', $event)
                    : null;
                app(PanelNotifier::class)->toUser(
                    $notifyUser,
                    'Invitation send failed',
                    "{$failedLabel} to {$guest} for {$eventTitle} failed. Try resending from Ekaadh.",
                    PushNotificationService::TYPE_INVITE_SEND_FAILED,
                    $url,
                    [
                        'invitation_id' => (string) $invitation->id,
                        'event_id' => (string) ($invitation->event_id ?? ''),
                    ],
                );
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Ticket>  $tickets
     */
    private function sendEmail(Order $order, $tickets): void
    {
        if (! $order->buyer_email) {
            Log::info('Ticket delivery email skipped (no email)', [
                'order' => $order->order_number,
            ]);

            return;
        }

        $lines = $tickets->map(function (Ticket $t) {
            return '- '.$t->ticket_code.' ('.$t->ticket_type_name.') '.$this->qr->publicUrl($t->ticket_code);
        })->implode("\n");

        $body = "Hi {$order->buyer_name},\n\n"
            ."Your Ekaadh tickets for {$order->event->title} are ready.\n"
            ."Order: {$order->order_number}\n\n"
            ."{$lines}\n\n"
            ."Show your QR / ticket code at the entrance.\n\n"
            .'— Ekaadh';

        try {
            Mail::raw($body, function ($message) use ($order) {
                $message->to($order->buyer_email, $order->buyer_name)
                    ->subject("Your Ekaadh tickets — {$order->event->title}");
            });

            Log::info('Ticket delivery email queued/sent', [
                'order' => $order->order_number,
                'email' => $this->redactEmail($order->buyer_email),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Ticket delivery email failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Ticket>  $tickets
     */
    private function sendPaidNotificationSms(Order $order, $tickets): void
    {
        if ($order->event?->is_private) {
            return;
        }

        $title = trim((string) ($order->event?->title ?? 'munaasabadda')) ?: 'munaasabadda';
        $number = (string) $order->order_number;
        $links = $tickets
            ->take(3)
            ->map(fn (Ticket $t) => $this->qr->publicUrl($t->ticket_code))
            ->filter(fn ($url) => is_string($url) && $url !== '')
            ->implode(' ');

        // Ekaadh: Dalabkaaga waa la xaqiijiyay — {event}. IDga {order}. Fur tigidhadaada: {ticket links}.
        $body = "Ekaadh: Dalabkaaga waa la xaqiijiyay - {$title}. IDga {$number}.";
        if ($links !== '') {
            $body .= " Fur tigidhadaada: {$links}.";
            if ($tickets->count() > 3) {
                $body = rtrim($body, '.').'....';
            }
        }

        $this->deliverSms($order->buyer_phone, $body);
    }

    /**
     * SMS reminder ~2 hours before the event (public tickets + private invites).
     */
    public function sendEventReminderSms(Ticket $ticket): string
    {
        $ticket->loadMissing(['event', 'orderItem.order', 'invitation']);
        $event = $ticket->event;
        $title = $event?->title ?? 'munaasabadda';
        $phone = $ticket->invitation?->guest_phone
            ?: $ticket->orderItem?->order?->buyer_phone;
        $url = $this->qr->publicUrl($ticket->ticket_code);

        $body = "Ekaadh: Xusuusin — {$title} waxay bilaabanaysaa 2 saacadood gudahood. "
            ."Fur tigidhkaaga: {$url}.";

        return $this->deliverSms($phone, $body);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Ticket>  $tickets
     */
    private function sendOrderPush(Order $order, $tickets): void
    {
        if ($order->event?->is_private) {
            return;
        }

        $title = $order->event?->title ?? 'munaasabadda';
        $this->push->sendToPhone(
            $order->buyer_phone,
            'Tigidhadaada waa diyaar',
            "Tigidhadaada {$title} waxay ku diyaar yihiin Ekaadh.",
            PushNotificationService::TYPE_TICKETS_READY,
            [
                'event_id' => (string) ($order->event_id ?? ''),
                'order_number' => (string) $order->order_number,
            ],
            true,
        );

        if ($order->user_id) {
            $user = $order->user_id ? \App\Models\User::query()->find($order->user_id) : null;
            if ($user && $user->phone !== $order->buyer_phone) {
                $this->push->sendToUser(
                    $user,
                    'Tigidhadaada waa diyaar',
                    "Tigidhadaada {$title} waxay ku diyaar yihiin Ekaadh.",
                    PushNotificationService::TYPE_TICKETS_READY,
                    [
                        'event_id' => (string) ($order->event_id ?? ''),
                        'order_number' => (string) $order->order_number,
                    ],
                    true,
                );
            }
        }
    }

    private function deliverSms(?string $phone, string $body): string
    {
        if (! $phone) {
            Log::info('SMS skipped (no phone)');

            return 'skipped';
        }

        if (! $this->sms->enabled()) {
            Log::info('SMS stub — Telesom not configured', [
                'phone' => $this->redactPhone($phone),
            ]);

            return 'skipped';
        }

        try {
            $this->sms->send($phone, $body);

            return 'sent';
        } catch (\Throwable $e) {
            Log::warning('SMS failed', [
                'phone' => $this->redactPhone($phone),
                'error' => $e->getMessage(),
            ]);

            return 'failed';
        }
    }

    private function deliverInviteWhatsApp(
        ?string $phone,
        string $guestName,
        string $eventTitle,
        int $qty,
        string $inviteUrl,
    ): string {
        if (! $phone) {
            Log::info('Invitation WhatsApp skipped (no phone)');

            return 'skipped';
        }

        if (! $this->whatsapp->canSendInvite()) {
            Log::info('Invitation WhatsApp skipped (Cloud API not configured)', [
                'phone' => $this->redactPhone($phone),
            ]);

            return 'skipped';
        }

        try {
            $this->whatsapp->sendTemplate(
                $phone,
                $this->whatsapp->inviteTemplate(),
                [
                    $guestName,
                    $eventTitle,
                    (string) $qty,
                    $inviteUrl,
                ],
            );

            return 'sent';
        } catch (\Throwable $e) {
            Log::warning('Invitation WhatsApp failed', [
                'phone' => $this->redactPhone($phone),
                'error' => $e->getMessage(),
            ]);

            return 'failed';
        }
    }

    private function redactPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if (strlen($digits) < 4) {
            return '***';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }

    private function redactEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return '***';
        }

        [$local, $domain] = explode('@', $email, 2);

        return substr($local, 0, 1).'***@'.$domain;
    }
}
