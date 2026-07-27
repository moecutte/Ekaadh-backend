<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Services\OtpService;
use App\Services\TicketQrService;
use App\Support\Phone;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Throwable;

class TicketController extends Controller
{
    public function __construct(
        private TicketQrService $qr,
        private OtpService $otp,
    ) {}

    public function index(Request $request): View
    {
        $phone = trim((string) $request->input('phone', ''));
        $otpToken = trim((string) $request->input('otp_token', ''));
        $tickets = collect();
        $error = null;
        $searched = false;
        $accountMode = false;

        $user = $request->user();
        $isCustomer = $user instanceof User && $user->isCustomer();

        if ($isCustomer && ! $request->filled('phone') && ! $request->boolean('guest')) {
            $accountMode = true;
            $tickets = Ticket::query()
                ->with(['event', 'orderItem.order'])
                ->whereHas('orderItem.order', function ($q) use ($user) {
                    $q->where('status', 'paid')
                        ->where(function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                            if ($user->phone) {
                                $q->orWhereIn('buyer_phone', Phone::variants($user->phone));
                            }
                        });
                })
                ->latest()
                ->get()
                ->map(fn (Ticket $ticket) => $this->decorate($ticket));
        } elseif ($phone !== '' && $otpToken !== '') {
            $searched = true;
            try {
                $this->otp->assertVerified($phone, OtpService::PURPOSE_FIND_TICKETS, $otpToken);
                $normalized = $this->otp->normalize($phone);
                $phone = $normalized;
                $tickets = $this->otp->findableTicketsForPhone($normalized)
                    ->map(fn (Ticket $ticket) => $this->decorate($ticket));
            } catch (\Illuminate\Validation\ValidationException $e) {
                $error = collect($e->errors())->flatten()->first() ?: 'Could not verify phone.';
            }
        } elseif ($request->filled('phone') || $request->filled('otp_token')) {
            $searched = true;
            $error = 'Confirm your phone with the code we sent to view tickets.';
        }

        return view('tickets.index', [
            'phone' => $phone,
            'order' => '',
            'tickets' => $tickets,
            'searched' => $searched,
            'accountMode' => $accountMode,
            'isCustomer' => $isCustomer,
            'error' => $error,
            'otpMode' => true,
            'otpToken' => $searched && $tickets->isNotEmpty() ? $otpToken : '',
            'otpSendUrl' => url('/api/v1/otp/send'),
            'otpVerifyUrl' => url('/api/v1/otp/verify'),
        ]);
    }

    public function show(string $code): View
    {
        $ticket = Ticket::query()
            ->with(['event', 'orderItem.order', 'invitation'])
            ->where('ticket_code', strtoupper($code))
            ->firstOrFail();

        $payload = $this->qr->payload($ticket->ticket_code);
        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data='.urlencode($payload);
        $invitationUrl = $ticket->invitation?->publicUrl();

        return view('tickets.show', compact('ticket', 'payload', 'qrImage', 'invitationUrl'));
    }

    public function pdf(string $code): Response
    {
        $ticket = Ticket::query()
            ->with(['event', 'orderItem.order'])
            ->where('ticket_code', strtoupper($code))
            ->firstOrFail();

        $payload = $this->qr->payload($ticket->ticket_code);
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data='.urlencode($payload);
        $qrDataUri = $this->imageDataUri($qrUrl) ?? $qrUrl;

        $design = \App\Support\TicketDesigns::resolveForEvent($ticket->event);
        $design['field_values'] = $ticket->event?->invitation_field_values ?? [];

        $isOverlay = ($design['render_mode'] ?? '') === 'overlay' && ! empty($design['graphic_url']);

        if ($isOverlay) {
            $fields = collect($design['fields'] ?? [])->where('show_on_card', true)->values()->all();
            \App\Support\InvitationFonts::preparePdfFonts($fields);

            $graphicDataUri = $this->imageDataUri($design['graphic_url'])
                ?? $this->localImageDataUri($design['graphic_path'] ?? null);
            $pdf = Pdf::loadView('tickets.pdf-overlay', [
                'ticket' => $ticket,
                'qrDataUri' => $qrDataUri,
                'design' => $design,
                'graphicDataUri' => $graphicDataUri ?? $design['graphic_url'],
            ])->setPaper([0, 0, 420, 595], 'portrait');
            $pdf->getDomPDF()->getFontMetrics()->loadFontFamilies();
        } else {
            $pdf = Pdf::loadView('tickets.pdf', [
                'ticket' => $ticket,
                'qrDataUri' => $qrDataUri,
                'coverDataUri' => $this->imageDataUri($ticket->event?->cover_image),
            ])->setPaper([0, 0, 420, 680], 'portrait');
        }

        $safeCode = preg_replace('/[^\w\-]+/', '_', $ticket->ticket_code) ?: 'ticket';

        return $pdf->download('Ekaadh-'.$safeCode.'.pdf');
    }

    private function decorate(Ticket $ticket): Ticket
    {
        $payload = $this->qr->payload($ticket->ticket_code);
        $ticket->qr_image = 'https://api.qrserver.com/v1/create-qr-code/?size=114x114&data='.urlencode($payload);
        $ticket->ticket_url = $this->qr->publicUrl($ticket->ticket_code);

        return $ticket;
    }

    private function imageDataUri(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $fromLocal = $this->localImageDataUriFromUrl($url);
        if ($fromLocal !== null) {
            return $fromLocal;
        }

        try {
            $response = Http::timeout(12)->get($url);
            if (! $response->successful() || $response->body() === '') {
                return null;
            }

            $mime = $response->header('Content-Type') ?: 'image/png';
            if (str_contains($mime, ';')) {
                $mime = trim(explode(';', $mime, 2)[0]);
            }

            return 'data:'.$mime.';base64,'.base64_encode($response->body());
        } catch (Throwable) {
            return null;
        }
    }

    private function localImageDataUri(?string $path): ?string
    {
        if ($path === null || $path === '' || str_starts_with($path, 'http')) {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', $path), '/');
        $candidates = [
            public_path($relative),
            storage_path('app/public/'.$relative),
            base_path($relative),
        ];

        foreach ($candidates as $full) {
            if (is_file($full)) {
                return $this->fileToDataUri($full);
            }
        }

        return null;
    }

    private function localImageDataUriFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        $relative = ltrim($path, '/');
        // Strip common public base segments (e.g. ekaadh/Ekaadh-backend/public/...)
        if (preg_match('#(?:^|/)public/(.+)$#', $relative, $m)) {
            $relative = $m[1];
        }

        return $this->localImageDataUri($relative);
    }

    private function fileToDataUri(string $fullPath): ?string
    {
        try {
            $bytes = file_get_contents($fullPath);
            if ($bytes === false || $bytes === '') {
                return null;
            }
            $mime = mime_content_type($fullPath) ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode($bytes);
        } catch (Throwable) {
            return null;
        }
    }
}
