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
                ->with(['event', 'orderItem.order', 'invitation'])
                ->where(function ($q) use ($user) {
                    $q->whereHas('orderItem.order', function ($q) use ($user) {
                        $q->where('status', 'paid')
                            ->where(function ($q) use ($user) {
                                $q->where('user_id', $user->id);
                                if ($user->phone) {
                                    $q->orWhereIn('buyer_phone', Phone::variants($user->phone))
                                        ->orWhereHas('payment', function ($q) use ($user) {
                                            $q->whereIn('phone_number', Phone::variants($user->phone));
                                        });
                                }
                            });
                    });
                    if ($user->phone) {
                        $q->orWhereHas('invitation', function ($q) use ($user) {
                            $q->where('status', 'active')
                                ->whereIn('guest_phone', Phone::variants($user->phone));
                        });
                    }
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
                    ->values()
                    ->map(fn (Ticket $ticket) => $this->decorate($ticket));
            } catch (\Illuminate\Validation\ValidationException $e) {
                $error = collect($e->errors())->flatten()->first() ?: 'Could not verify phone.';
            }
        } elseif ($request->filled('phone') || $request->filled('otp_token')) {
            $searched = true;
            $error = 'Confirm your phone with the code we sent to view tickets.';
        }

        $filterOptions = $this->ticketFilterOptions($tickets);
        $filtersActive = false;
        if ($tickets->isNotEmpty()) {
            [$tickets, $filtersActive] = $this->filterTickets($tickets, $request);
        }

        $when = $request->string('when')->toString();
        if (! in_array($when, ['all', 'upcoming', 'past'], true)) {
            $when = 'all';
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
            'otpToken' => $searched && $otpToken !== '' ? $otpToken : '',
            'otpSendUrl' => route('otp.send'),
            'otpVerifyUrl' => route('otp.verify'),
            'filterOptions' => $filterOptions,
            'filtersActive' => $filtersActive,
            'when' => $when,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Ticket>  $tickets
     * @return array{0: \Illuminate\Support\Collection<int, Ticket>, 1: bool}
     */
    private function filterTickets($tickets, Request $request): array
    {
        $when = $request->string('when')->toString();
        if (! in_array($when, ['all', 'upcoming', 'past'], true)) {
            $when = 'all';
        }
        $status = $request->string('status')->toString();
        $origin = $request->string('origin')->toString();
        if (! in_array($origin, ['private', 'invitation', 'website_paid', 'website_free'], true)) {
            $origin = '';
        }
        $q = mb_strtolower($request->string('q')->trim()->toString());
        $category = $request->string('category')->trim()->toString();
        $city = $request->string('city')->trim()->toString();

        $filtersActive = collect([
            $when !== 'all' ? $when : null,
            $status,
            $origin,
            $q,
            $category,
            $city,
        ])->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();

        $filtered = $tickets->filter(function (Ticket $ticket) use ($when, $status, $origin, $q, $category, $city) {
            $event = $ticket->event;
            $expired = $event?->isExpired() ?? false;

            if ($when === 'upcoming' && $expired) {
                return false;
            }
            if ($when === 'past' && ! $expired) {
                return false;
            }

            if ($status !== '' && strtolower((string) $ticket->status) !== strtolower($status)) {
                return false;
            }

            if ($origin !== '' && $this->ticketOrigin($ticket) !== $origin) {
                return false;
            }

            if ($category !== '' && (string) ($event?->category ?? '') !== $category) {
                return false;
            }

            if ($city !== '' && (string) ($event?->city ?? '') !== $city) {
                return false;
            }

            if ($q !== '') {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $event?->title,
                    $event?->venue,
                    $event?->city,
                    $event?->category,
                    $ticket->ticket_type_name,
                    $ticket->ticket_code,
                    $ticket->holder_name,
                    $ticket->origin_label ?? null,
                ])));
                if (! str_contains($haystack, $q)) {
                    return false;
                }
            }

            return true;
        })->values();

        return [$filtered, $filtersActive];
    }

    /**
     * private | invitation | website_paid | website_free
     */
    private function ticketOrigin(Ticket $ticket): string
    {
        $event = $ticket->event;
        if ($event?->is_private) {
            return 'private';
        }

        $order = $ticket->orderItem?->order;
        if ($ticket->invitation_id || ($order && $order->isInvitation())) {
            return 'invitation';
        }

        if ($event?->isFreeEvent()) {
            return 'website_free';
        }

        return 'website_paid';
    }

    private function ticketOriginLabel(string $origin): string
    {
        return match ($origin) {
            'private' => __('ui.ticket_origin_private'),
            'invitation' => __('ui.ticket_origin_invitation'),
            'website_paid' => __('ui.ticket_origin_website_paid'),
            'website_free' => __('ui.ticket_origin_website_free'),
            default => __('ui.ticket_origin_website_paid'),
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Ticket>  $tickets
     * @return array{categories: list<string>, cities: list<string>}
     */
    private function ticketFilterOptions($tickets): array
    {
        $categories = $tickets
            ->map(fn (Ticket $t) => trim((string) ($t->event?->category ?? '')))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $cities = $tickets
            ->map(fn (Ticket $t) => trim((string) ($t->event?->city ?? '')))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return compact('categories', 'cities');
    }

    private function decorate(Ticket $ticket): Ticket
    {
        $ticket->qr_image = $this->qr->imageUrl($ticket->ticket_code);
        $ticket->ticket_url = $this->qr->publicUrl($ticket->ticket_code);
        $origin = $this->ticketOrigin($ticket);
        $ticket->origin = $origin;
        $ticket->origin_label = $this->ticketOriginLabel($origin);

        return $ticket;
    }

    public function show(string $code): View
    {
        $ticket = Ticket::query()
            ->with(['event.invitationDesign.fields', 'orderItem.order', 'invitation'])
            ->where('ticket_code', strtoupper($code))
            ->firstOrFail();

        $payload = $this->qr->payload($ticket->ticket_code);
        $qrImage = $this->qr->imageUrl($ticket->ticket_code);
        $invitationUrl = $ticket->invitation?->publicUrl();

        $design = \App\Support\TicketDesigns::resolveForEvent($ticket->event);
        $design['field_values'] = \App\Support\InvitationDateFields::applyToValues(
            $design['fields'] ?? [],
            $ticket->event?->invitation_field_values ?? ($design['field_values'] ?? []),
            $ticket->event?->event_date,
            $ticket->event?->event_time,
        );

        return view('tickets.show', compact('ticket', 'payload', 'qrImage', 'invitationUrl', 'design'));
    }

    public function pdf(string $code): Response
    {
        $ticket = Ticket::query()
            ->with(['event', 'orderItem.order'])
            ->where('ticket_code', strtoupper($code))
            ->firstOrFail();

        $qrDataUri = $this->qr->pngDataUri($ticket->ticket_code, 400);

        $design = \App\Support\TicketDesigns::resolveForEvent($ticket->event);
        $design['field_values'] = \App\Support\InvitationDateFields::applyToValues(
            $design['fields'] ?? [],
            $ticket->event?->invitation_field_values ?? ($design['field_values'] ?? []),
            $ticket->event?->event_date,
            $ticket->event?->event_time,
        );

        $isOverlay = ! empty($design['graphic_url']) || ! empty($design['graphic_path'])
            || (($design['render_mode'] ?? '') === 'overlay');

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

    public function qrImage(string $code): Response
    {
        $ticket = Ticket::query()
            ->where('ticket_code', strtoupper($code))
            ->firstOrFail();

        $png = \App\Support\QrPng::bytes($this->qr->payload($ticket->ticket_code), 400);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=300',
        ]);
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
