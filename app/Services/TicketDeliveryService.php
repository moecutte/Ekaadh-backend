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
        private TextBeeSmsService $sms,
    ) {}

    public function sendForOrder(Order $order): void
    {
        $order->loadMissing(['items.tickets', 'event']);

        $tickets = $order->items->flatMap->tickets;
        if ($tickets->isEmpty()) {
            return;
        }

        $this->sendEmail($order, $tickets);
        $this->sendOrderSms($order, $tickets);
        $this->sendWhatsAppStub($order->buyer_phone, $tickets->count(), $order->order_number);
    }

    public function sendForInvitation(EventInvitation $invitation): void
    {
        $invitation->loadMissing(['tickets', 'event', 'ticketType']);

        if (! $invitation->isActive()) {
            return;
        }

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

        $smsStatus = $this->deliverSms($invitation->guest_phone, $body);
        $waStatus = $this->deliverWhatsAppStub($invitation->guest_phone, $qty, $inviteUrl);

        $invitation->update([
            'sms_status' => $smsStatus,
            'whatsapp_status' => $waStatus,
            'last_sent_at' => now(),
        ]);
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
    private function sendOrderSms(Order $order, $tickets): void
    {
        $first = $tickets->first();
        $url = $first ? $this->qr->publicUrl($first->ticket_code) : url('/');
        $title = $order->event?->title ?? 'your event';
        $body = "Ekaadh: Your tickets for {$title} ({$tickets->count()}). Open: {$url}";

        $this->deliverSms($order->buyer_phone, $body);
    }

    private function deliverSms(?string $phone, string $body): string
    {
        if (! $phone) {
            Log::info('Ticket delivery SMS skipped (no phone)');

            return 'skipped';
        }

        if (! $this->sms->enabled()) {
            Log::info('Ticket delivery SMS (stub — TextBee not configured)', [
                'phone' => $this->redactPhone($phone),
            ]);

            return 'skipped';
        }

        try {
            $this->sms->send($phone, $body);

            return 'sent';
        } catch (\Throwable $e) {
            Log::warning('Ticket delivery SMS failed', [
                'phone' => $this->redactPhone($phone),
                'error' => $e->getMessage(),
            ]);

            return 'failed';
        }
    }

    private function deliverWhatsAppStub(?string $phone, int $ticketCount, string $context): string
    {
        Log::info('Ticket delivery WhatsApp (stub — awaiting Business API)', [
            'phone' => $this->redactPhone($phone),
            'ticket_count' => $ticketCount,
            'context' => $context,
        ]);

        return 'skipped';
    }

    private function sendWhatsAppStub(?string $phone, int $ticketCount, string $context): void
    {
        $this->deliverWhatsAppStub($phone, $ticketCount, $context);
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
