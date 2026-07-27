<?php

namespace App\Services;

class TicketQrService
{
    public function payload(string $ticketCode): string
    {
        $sig = $this->signature($ticketCode);

        return "EKAADH|{$ticketCode}|{$sig}";
    }

    public function signature(string $ticketCode): string
    {
        return hash_hmac('sha256', $ticketCode, $this->secret());
    }

    public function verify(string $payload): ?string
    {
        $parts = explode('|', $payload);
        if (count($parts) !== 3 || $parts[0] !== 'EKAADH') {
            // Plain ticket codes allowed for manual entry at the gate.
            if (preg_match('/^EKD-[A-Z0-9]+$/i', $payload)) {
                return strtoupper($payload);
            }

            return null;
        }

        [$prefix, $code, $sig] = $parts;
        if (! hash_equals($this->signature($code), $sig)) {
            return null;
        }

        return strtoupper($code);
    }

    public function publicUrl(string $ticketCode): string
    {
        return url('/t/'.$ticketCode);
    }

    private function secret(): string
    {
        $secret = (string) config('services.ticket_qr.secret');

        return $secret !== '' ? $secret : (string) config('app.key');
    }
}
