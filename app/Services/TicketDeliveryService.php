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
        $order->loadMissing(['items.tickets', 'event']);

        $tickets = $order->items->flatMap->tickets;
        if ($tickets->isEmpty()) {
            return;
        }

        $this->sendEmail($order, $tickets);
        $this->sendPaidNotificationSms($order);
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
        $eventTitle = $invitation->event?->title ?? 'your event';
        $qty = $invitation->tickets->where('status', '!=', 'cancelled')->count() ?: $invitation->quantity;
        $guestName = $invitation->guest_name ?: 'Guest';

        $codes = $invitation->tickets
            ->where('status', '!=', 'cancelled')
            ->take(3)
            ->map(fn (Ticket $t) => $t->ticket_code)
            ->implode(', ');

        $body = "Ekaadh: Hi {$guestName}, you're invited to {$eventTitle}. "
            ."{$qty} ticket(s). Open your invitation: {$inviteUrl}";

        if ($codes !== '') {
            $body .= " Codes: {$codes}";
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
            'You\'re invited',
            "You're invited to {$eventTitle}. Open Ekaadh to view your invitation.",
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

    private function sendPaidNotificationSms(Order $order): void
    {
        $title = $order->event?->title ?? 'your event';
        $number = $order->order_number;
        $body = ((float) $order->total_amount) > 0
            ? "Ekaadh: Payment confirmed for {$title}. Order {$number}."
            : "Ekaadh: Your order is confirmed for {$title}. Order {$number}.";

        $this->deliverSms($order->buyer_phone, $body);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Ticket>  $tickets
     */
    private function sendOrderPush(Order $order, $tickets): void
    {
        if ($order->event?->is_private) {
            return;
        }

        $title = $order->event?->title ?? 'your event';
        $this->push->sendToPhone(
            $order->buyer_phone,
            'Tickets ready',
            "Your tickets for {$title} are ready in Ekaadh.",
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
                    'Tickets ready',
                    "Your tickets for {$title} are ready in Ekaadh.",
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
