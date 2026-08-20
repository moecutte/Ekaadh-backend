<?php

namespace App\Http\Controllers\Web\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Organizer\Concerns\ResolvesOrganizerProfile;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventGalleryImage;
use App\Models\EventProgrammeItem;
use App\Models\EventSpeaker;
use App\Models\Order;
use App\Models\OrganizerPackage;
use App\Models\OrganizerProfile;
use App\Models\TicketType;
use App\Services\InvitationService;
use App\Services\OrderService;
use App\Services\OrganizerEventPackageService;
use App\Services\Payments\WaafiPayGateway;
use App\Support\PaymentMessage;
use App\Support\Phone;
use App\Support\PublicUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EventController extends Controller
{
    use ResolvesOrganizerProfile;

    public function __construct(
        private OrganizerEventPackageService $packages,
        private InvitationService $invitations,
    ) {}

    public function index(Request $request): View
    {
        $profile = $this->organizerProfile();

        $baseQuery = Event::query()->where('organizer_id', $profile->id);

        $query = (clone $baseQuery)->with(['ticketTypes', 'package'])->withCount('invitations');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('venue', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        if ($city = $request->string('city')->trim()->toString()) {
            $query->where('city', $city);
        }

        if ($from = $request->string('date_from')->toString()) {
            $query->whereDate('event_date', '>=', $from);
        }

        if ($to = $request->string('date_to')->toString()) {
            $query->whereDate('event_date', '<=', $to);
        }

        $sort = $request->string('sort')->toString();
        match ($sort) {
            'date_asc' => $query->orderBy('event_date')->orderBy('event_time'),
            'date_desc' => $query->orderByDesc('event_date')->orderByDesc('event_time'),
            'title' => $query->orderBy('title'),
            default => $query->latest(),
        };

        $events = $query->paginate(15)->withQueryString();

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $filterOptions = [
            'categories' => Category::activeNames(),
            'cities' => City::activeNames(),
        ];

        $filterKeys = ['q', 'status', 'category', 'city', 'date_from', 'date_to', 'sort'];
        $filtersActive = collect($request->only($filterKeys))
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->isNotEmpty();

        return view('organizer.events.index', compact(
            'events',
            'filterOptions',
            'filtersActive',
            'statusCounts',
        ));
    }

    public function create(): View
    {
        $profile = $this->organizerProfile();

        return view('organizer.events.form', $this->formPayload(
            new Event(['status' => 'draft', 'category' => Category::activeNames()[0] ?? 'Music', 'pricing_type' => 'paid']),
            collect([
                ['name' => 'General Admission', 'price' => 15, 'quantity_available' => 100, 'max_per_order' => 5, 'description' => ''],
            ]),
            $profile
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $this->organizerProfile();
        $data = $this->validated($request);
        $pricingType = $this->validatedPricingType($request);
        $package = $this->resolvedFreeEventPackage($request, $pricingType);
        $tickets = $this->validatedTickets($request, $pricingType);
        $this->assertEventExtras($request);

        if ($error = $this->capacityError($profile, $tickets, $pricingType, $package)) {
            return back()->withInput()->with('error', $error);
        }

        $pending = $this->pendingInvitationsFrom($request);
        if ($error = $this->pendingInviteCapacityError($tickets, $pending)) {
            return back()->withInput()->with('error', $error);
        }

        $wantsReview = $request->input('action') === 'publish';
        $status = $wantsReview ? 'pending_review' : 'draft';

        $event = Event::query()->create([
            'organizer_id' => $profile->id,
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'description' => $data['description'],
            'category' => $data['category'],
            'venue' => $data['venue'],
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'event_date' => $data['event_date'],
            'event_time' => $data['event_time'],
            'cover_image' => $this->storeCoverImage($request->file('cover_image')),
            'is_featured' => false,
            'is_private' => false,
            'pricing_type' => $pricingType,
            'package_id' => $package?->id,
            'pending_invitations' => $pending,
            'status' => $status === 'pending_review' && $pricingType === 'free' ? 'draft' : $status,
        ]);

        $this->syncTicketTypes($event, $tickets);
        $this->syncSpeakers($event, $request);
        $this->syncProgramme($event, $request);
        $this->syncGallery($event, $request);

        return $this->afterSave($event->fresh(['package', 'ticketTypes']), $wantsReview, created: true);
    }

    public function edit(Event $event): View
    {
        $this->authorizeEvent($event);
        $profile = $this->organizerProfile();

        return view('organizer.events.form', $this->formPayload(
            $event->load(['package', 'speakers', 'programmeItems', 'galleryImages']),
            $event->ticketTypes->map(fn (TicketType $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'description' => $t->description,
                'price' => $t->price,
                'quantity_available' => $t->quantity_available,
                'max_per_order' => $t->max_per_order,
            ]),
            $profile
        ));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeEvent($event);
        $profile = $this->organizerProfile();
        $previousStatus = $event->status;
        $data = $this->validated($request, $event);
        $pricingType = $this->validatedPricingType($request, $event);
        $package = $this->resolvedFreeEventPackage($request, $pricingType, $event);
        $tickets = $this->validatedTickets($request, $pricingType);
        $this->assertEventExtras($request);

        if ($error = $this->capacityError($profile, $tickets, $pricingType, $package, $event)) {
            return back()->withInput()->with('error', $error);
        }

        $pending = $request->exists('invite_channel') ? $this->pendingInvitationsFrom($request) : null;
        if ($request->exists('invite_channel')) {
            $sold = $event->ticketTypes->sum('quantity_sold');
            if ($error = $this->pendingInviteCapacityError($tickets, $pending, (int) $sold)) {
                return back()->withInput()->with('error', $error);
            }
        }

        $status = $event->status;
        $wantsReview = $request->input('action') === 'publish';
        if ($request->input('action') === 'draft') {
            $status = 'draft';
        } elseif ($wantsReview && ! ($pricingType === 'free' && $package && $package->chargeAmount() > 0 && ! ($event->package_id === $package->id && $event->packageIsPaid()))) {
            $status = 'pending_review';
        }

        $coverImage = $event->getRawOriginal('cover_image');
        if ($request->hasFile('cover_image')) {
            $this->deleteLocalCoverImage($coverImage);
            $coverImage = $this->storeCoverImage($request->file('cover_image'));
        }

        $samePaidPackage = $pricingType === 'free'
            && $event->packageIsPaid()
            && (int) $event->package_id === (int) ($package?->id ?? 0);

        $event->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $data['category'],
            'venue' => $data['venue'],
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'event_date' => $data['event_date'],
            'event_time' => $data['event_time'],
            'cover_image' => $coverImage,
            'is_private' => false,
            'pricing_type' => $pricingType,
            'package_id' => $package?->id,
            'package_paid_at' => $samePaidPackage ? $event->package_paid_at : null,
            'status' => $status,
        ]);

        if ($request->exists('invite_channel')) {
            $event->update(['pending_invitations' => $pending]);
        }

        $this->syncTicketTypes($event, $tickets, true);
        $this->syncSpeakers($event, $request, true);
        $this->syncProgramme($event, $request, true);
        $this->syncGallery($event, $request, true);

        return $this->afterSave($event->fresh(['package', 'ticketTypes']), $wantsReview, created: false, previousStatus: $previousStatus);
    }

    public function payForm(Event $event): View|RedirectResponse
    {
        $this->authorizeEvent($event);
        $event->load(['package', 'ticketTypes']);

        if (! $event->needsPackagePayment()) {
            return redirect()
                ->route('organizer.events.edit', $event)
                ->with('success', 'This event does not need a package payment.');
        }

        try {
            $order = $this->packages->pendingOrCreate($event, auth()->user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->route('organizer.events.edit', $event)
                ->withErrors($e->errors())
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return view('organizer.events.pay', [
            'event' => $event,
            'order' => $order,
            'allowForceFail' => OrderService::allowsForceFail(),
            'waafiSandbox' => (bool) config('waafipay.sandbox'),
            'waafiTestWallets' => config('waafipay.test_wallets', []),
        ]);
    }

    public function pay(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeEvent($event);
        $event->load(['package', 'ticketTypes', 'organizer']);

        $data = $request->validate([
            'payment_method' => ['required', 'in:waafipay'],
            'force_fail' => ['sometimes', 'boolean'],
            'wallet_pin' => ['nullable', 'string', 'max:8'],
            'buyer_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $walletPin = WaafiPayGateway::sandboxPin($data['wallet_pin'] ?? null);
        if (config('waafipay.sandbox') && $walletPin === null) {
            return back()->withErrors([
                'wallet_pin' => WaafiPayGateway::sandboxPinError($data['wallet_pin'] ?? null),
            ]);
        }

        try {
            $order = $this->packages->pendingOrCreate($event, auth()->user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $chargePhone = Phone::normalize(auth()->user()->phone ?: $event->organizer?->business_phone);
        if (config('waafipay.sandbox')) {
            $chargePhone = Phone::normalize($data['buyer_phone'] ?? '');
            if ($chargePhone === '') {
                return back()->withErrors([
                    'buyer_phone' => __('ui.sandbox_charge_phone_required'),
                ]);
            }
        }

        try {
            $order = $this->packages->pay(
                $order,
                $data['payment_method'],
                $chargePhone,
                OrderService::allowsForceFail() && $request->boolean('force_fail'),
                $walletPin
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        if ($order->status === 'paid') {
            return redirect()
                ->route('organizer.events.index')
                ->with('success', $event->fresh()->status === 'pending_review'
                    ? 'Package paid. Event submitted for admin review.'
                    : 'Package paid. You can submit this event for review when ready.');
        }

        if ($order->status === 'pending') {
            return redirect()
                ->route('organizer.events.pay', $event)
                ->with('success', __('ui.payment_pending_hint'));
        }

        $order->loadMissing('payment');

        return redirect()
            ->route('organizer.events.pay', $event)
            ->with('error', PaymentMessage::forOrder($order));
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorizeEvent($event);

        if (Order::query()->where('event_id', $event->id)->where('status', 'paid')->exists()) {
            return back()->with('error', 'Cannot delete an event with paid orders. Cancel it instead.');
        }

        $event->delete();

        return redirect()->route('organizer.events.index')->with('success', 'Event deleted.');
    }

    private function authorizeEvent(Event $event): void
    {
        abort_unless($event->organizer_id === $this->organizerProfile()->id, 403);
    }

    /**
     * @param  array<int, array<string, mixed>>  $tickets
     */
    private function capacityError(
        OrganizerProfile $profile,
        array $tickets,
        string $pricingType,
        ?OrganizerPackage $freePackage,
        ?Event $event = null,
    ): ?string {
        $totalTickets = collect($tickets)->sum(fn ($row) => (int) ($row['quantity_available'] ?? 0));

        if ($pricingType === 'free') {
            if (! $freePackage) {
                return 'Choose a free-event package.';
            }

            return $freePackage->ticketLimitError($totalTickets);
        }

        return $this->packageLimitError($profile, $tickets, $event);
    }

    /**
     * @param  array<int, array<string, mixed>>  $tickets
     */
    private function packageLimitError(OrganizerProfile $profile, array $tickets, ?Event $event = null): ?string
    {
        $package = $profile->package;
        if (! $package) {
            return null;
        }

        if ($package->max_events_per_year && ! $event) {
            $createdThisYear = Event::query()
                ->where('organizer_id', $profile->id)
                ->whereYear('created_at', now()->year)
                ->count();

            if ($createdThisYear >= $package->max_events_per_year) {
                return "Your {$package->name} package allows up to {$package->max_events_per_year} events per year. Upgrade your package to create more.";
            }
        }

        if ($package->max_tickets_per_event) {
            $totalTickets = collect($tickets)->sum(fn ($row) => (int) ($row['quantity_available'] ?? 0));
            if ($totalTickets > $package->max_tickets_per_event) {
                return "Your {$package->name} package allows up to {$package->max_tickets_per_event} tickets per event. Reduce capacity or upgrade your package.";
            }
        }

        return null;
    }

    private function validated(Request $request, ?Event $event = null): array
    {
        $allowedCategories = Category::activeNames();
        if ($event?->category && ! in_array($event->category, $allowedCategories, true)) {
            $allowedCategories[] = $event->category;
        }

        $allowedCities = City::activeNames();
        if ($event?->city && ! in_array($event->city, $allowedCities, true)) {
            $allowedCities[] = $event->city;
        }

        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:50', Rule::in($allowedCategories)],
            'venue' => ['required', 'string', 'max:180'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100', Rule::in($allowedCities)],
            'event_date' => ['required', 'date'],
            'event_time' => ['required'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);
    }

    private function validatedPricingType(Request $request, ?Event $event = null): string
    {
        $locked = $event && $event->exists && $event->pricingIsLocked();
        if ($locked && $event->pricing_type) {
            return $event->pricing_type;
        }

        $data = $request->validate([
            'pricing_type' => ['required', Rule::in(['free', 'paid'])],
        ]);

        return $data['pricing_type'];
    }

    private function resolvedFreeEventPackage(Request $request, string $pricingType, ?Event $event = null): ?OrganizerPackage
    {
        if ($pricingType !== 'free') {
            return null;
        }

        if ($event?->packageIsPaid() && $event->package_id) {
            return $event->package ?: OrganizerPackage::query()->find($event->package_id);
        }

        $data = $request->validate([
            'package_id' => [
                'required',
                'integer',
                Rule::exists('organizer_packages', 'id')
                    ->where('kind', OrganizerPackage::KIND_FREE_EVENT)
                    ->where('is_active', true),
            ],
        ]);

        return OrganizerPackage::query()->find($data['package_id']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validatedTickets(Request $request, string $pricingType = 'paid'): array
    {
        $data = $request->validate([
            'tickets' => ['required', 'array', 'min:1'],
            'tickets.*.id' => ['nullable', 'integer'],
            'tickets.*.name' => ['required', 'string', 'max:120'],
            'tickets.*.description' => ['nullable', 'string', 'max:255'],
            'tickets.*.price' => [$pricingType === 'free' ? 'nullable' : 'required', 'numeric', 'min:0'],
            'tickets.*.quantity_available' => ['required', 'integer', 'min:1'],
            'tickets.*.max_per_order' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $tickets = $data['tickets'];
        if ($pricingType === 'free') {
            foreach ($tickets as $i => $row) {
                $tickets[$i]['price'] = 0;
            }

            return $tickets;
        }

        $hasPriced = collect($tickets)->contains(fn ($row) => (float) ($row['price'] ?? 0) > 0);
        if (! $hasPriced) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'tickets' => ['Priced events need at least one ticket type with a price greater than $0.'],
            ]);
        }

        return $tickets;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $ticketTypes
     * @return array<string, mixed>
     */
    private function formPayload(Event $event, $ticketTypes, OrganizerProfile $profile): array
    {
        $freePackages = OrganizerPackage::query()
            ->active()
            ->freeEventPlans()
            ->ordered()
            ->get()
            ->map(fn (OrganizerPackage $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'price' => $package->chargeAmount(),
                'price_label' => $package->displayPrice(),
                'range_label' => $package->ticketRangeLabel(),
                'min' => $package->min_tickets_per_event,
                'max' => $package->max_tickets_per_event,
                'features' => $package->features ?? [],
                'highlighted' => (bool) $package->is_highlighted,
            ]);

        return [
            'event' => $event,
            'categories' => $event->exists ? $this->categoryOptionsFor($event) : Category::activeNames(),
            'cities' => $event->exists ? $this->cityOptionsFor($event) : City::activeNames(),
            'ticketTypes' => $ticketTypes,
            'freePackages' => $freePackages,
            'commissionRate' => $profile->effectiveCommissionRate(),
            'pricingLocked' => $event->exists && $event->pricingIsLocked(),
            'pendingInvites' => $this->pendingInviteRows($event),
            'inviteChannel' => old('invite_channel', data_get($event->pending_invitations, 'channel', 'whatsapp')),
            'speakerRows' => $this->speakerRowsForForm($event),
            'programmeRows' => $this->programmeRowsForForm($event),
            'galleryImages' => $event->exists ? $event->galleryImages : collect(),
        ];
    }

    private function afterSave(Event $event, bool $wantsReview, bool $created, ?string $previousStatus = null): RedirectResponse
    {
        $event->loadMissing('package');

        if ($event->isFreeEvent() && $event->package && $event->package->chargeAmount() <= 0) {
            $this->packages->markComplimentaryPackagePaid($event, $wantsReview ? 'pending_review' : 'draft');
            $event->refresh();
        }

        if ($event->needsPackagePayment()) {
            if ($wantsReview) {
                session(['event_package_after_pay.'.$event->id => 'pending_review']);
            }

            return redirect()
                ->route('organizer.events.pay', $event)
                ->with('success', $created
                    ? 'Event saved. Pay the selected package to continue.'
                    : 'Event updated. Pay the selected package to continue.');
        }

        if ($wantsReview && $event->status !== 'pending_review' && $event->status !== 'published') {
            $event->update(['status' => 'pending_review']);
        }

        $event = $event->fresh(['package', 'ticketTypes']);
        $inviteNote = '';
        if ($event->status === 'published' && $event->hasPendingInvitations()) {
            $flushed = $this->invitations->flushPending($event);
            $event->refresh();
            if ($flushed['created'] > 0) {
                $inviteNote = " Sent {$flushed['created']} complimentary invitation(s).";
            } elseif ($flushed['error']) {
                $inviteNote = ' Complimentary invitations are queued — send them from Guests after fixing: '.$flushed['error'];
            }
        } elseif ($event->hasPendingInvitations()) {
            $inviteNote = ' Complimentary invitations are queued and will send when the event is published.';
        }

        $status = $event->status;
        if ($status === 'pending_review' && $previousStatus !== 'pending_review') {
            app(\App\Services\PanelNotifier::class)->eventSubmittedForReview($event);
        }

        $message = match ($status) {
            'draft' => 'Event saved as draft.',
            'pending_review' => 'Event submitted for admin review.',
            default => $created ? 'Event created.' : 'Event updated.',
        };

        return redirect()->route('organizer.events.index')->with('success', $message.$inviteNote);
    }

    /**
     * @param  list<array<string, mixed>>  $tickets
     * @param  array{channel?: string, guests: list<array<string, mixed>>}|null  $pending
     */
    private function pendingInviteCapacityError(array $tickets, ?array $pending, int $alreadySold = 0): ?string
    {
        if (! $pending || ($pending['guests'] ?? []) === []) {
            return null;
        }

        $needed = collect($pending['guests'])->sum(fn ($guest) => max(1, (int) ($guest['quantity'] ?? 1)));
        $capacity = collect($tickets)->sum(fn ($ticket) => (int) ($ticket['quantity_available'] ?? 0));
        $remaining = max(0, $capacity - $alreadySold);

        if ($needed > $remaining) {
            return "Complimentary invitations need {$needed} seats, but only {$remaining} ticket(s) are available.";
        }

        return null;
    }

    /**
     * @return array{channel: string, guests: list<array{name: string, phone: string, quantity: int, ticket_name: string}>}|null
     */
    private function pendingInvitationsFrom(Request $request): ?array
    {
        $data = $request->validate([
            'invite_channel' => ['nullable', 'in:sms,whatsapp'],
            'invites' => ['nullable', 'array', 'max:'.Event::MAX_COMPLIMENTARY_GUESTS],
            'invites.*.name' => ['nullable', 'string', 'max:120'],
            'invites.*.phone' => ['nullable', 'string', 'max:30'],
            'invites.*.quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
            'invites.*.ticket_name' => ['nullable', 'string', 'max:120'],
        ]);

        $guests = [];
        foreach ($data['invites'] ?? [] as $row) {
            $phone = Phone::normalize((string) ($row['phone'] ?? ''));
            if ($phone === '') {
                continue;
            }

            $guests[] = [
                'name' => trim((string) ($row['name'] ?? '')),
                'phone' => $phone,
                'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
                'ticket_name' => trim((string) ($row['ticket_name'] ?? '')),
            ];
        }

        if ($guests === []) {
            return null;
        }

        if (count($guests) > Event::MAX_COMPLIMENTARY_GUESTS) {
            throw ValidationException::withMessages([
                'invites' => ['Complimentary guests are limited to '.Event::MAX_COMPLIMENTARY_GUESTS.' per event.'],
            ]);
        }

        return [
            'channel' => $data['invite_channel'] ?? 'whatsapp',
            'guests' => $guests,
        ];
    }

    /**
     * @return list<array{name: string, phone: string, quantity: int, ticket_name: string}>
     */
    private function pendingInviteRows(Event $event): array
    {
        $old = old('invites');
        if (is_array($old) && $old !== []) {
            return array_values($old);
        }

        $guests = is_array($event->pending_invitations) ? ($event->pending_invitations['guests'] ?? []) : [];

        return array_values($guests);
    }

    private function storeCoverImage(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        return PublicUpload::store($file, 'images/events', $filename);
    }

    private function deleteLocalCoverImage(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        $fullPath = public_path(ltrim($path, '/'));
        if (File::isFile($fullPath)) {
            File::delete($fullPath);
        }
    }

    /**
     * @return list<string>
     */
    private function categoryOptionsFor(Event $event): array
    {
        $names = Category::activeNames();
        if ($event->category && ! in_array($event->category, $names, true)) {
            array_unshift($names, $event->category);
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function cityOptionsFor(Event $event): array
    {
        $names = City::activeNames();
        if ($event->city && ! in_array($event->city, $names, true)) {
            array_unshift($names, $event->city);
        }

        return $names;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tickets
     */
    private function syncTicketTypes(Event $event, array $tickets, bool $isUpdate = false): void
    {
        $keepIds = [];

        foreach ($tickets as $row) {
            if ($isUpdate && ! empty($row['id'])) {
                $type = TicketType::query()
                    ->where('event_id', $event->id)
                    ->whereKey($row['id'])
                    ->first();

                if ($type) {
                    $type->update([
                        'name' => $row['name'],
                        'description' => $row['description'] ?? null,
                        'price' => $row['price'],
                        'quantity_available' => $row['quantity_available'],
                        'max_per_order' => $row['max_per_order'],
                    ]);
                    $keepIds[] = $type->id;

                    continue;
                }
            }

            $created = TicketType::query()->create([
                'event_id' => $event->id,
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'price' => $row['price'],
                'quantity_available' => $row['quantity_available'],
                'quantity_sold' => 0,
                'max_per_order' => $row['max_per_order'],
            ]);
            $keepIds[] = $created->id;
        }

        if ($isUpdate) {
            TicketType::query()
                ->where('event_id', $event->id)
                ->whereNotIn('id', $keepIds)
                ->where('quantity_sold', 0)
                ->delete();
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function speakerRowsForForm(Event $event): array
    {
        $old = old('speakers');
        if (is_array($old)) {
            return array_values($old);
        }

        if (! $event->exists) {
            return [];
        }

        return $event->speakers->map(fn (EventSpeaker $speaker) => [
            'id' => $speaker->id,
            'name' => $speaker->name,
            'role' => $speaker->role,
            'bio' => $speaker->bio,
            'photo_url' => $speaker->photo,
        ])->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function programmeRowsForForm(Event $event): array
    {
        $old = old('programme');
        if (is_array($old)) {
            return array_values($old);
        }

        if (! $event->exists) {
            return [];
        }

        return $event->programmeItems->map(fn (EventProgrammeItem $item) => [
            'id' => $item->id,
            'starts_at' => EventProgrammeItem::clockValue($item->starts_at),
            'ends_at' => EventProgrammeItem::clockValue($item->ends_at),
            'title' => $item->title,
            'description' => $item->description,
        ])->values()->all();
    }

    private function assertEventExtras(Request $request): void
    {
        $request->validate([
            'speakers' => ['nullable', 'array', 'max:20'],
            'speakers.*.id' => ['nullable', 'integer'],
            'speakers.*.name' => ['nullable', 'string', 'max:120'],
            'speakers.*.role' => ['nullable', 'string', 'max:120'],
            'speakers.*.bio' => ['nullable', 'string', 'max:500'],
            'speakers.*.photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'programme' => ['nullable', 'array', 'max:40'],
            'programme.*.id' => ['nullable', 'integer'],
            'programme.*.starts_at' => ['nullable', 'string', 'max:8'],
            'programme.*.ends_at' => ['nullable', 'string', 'max:8'],
            'programme.*.title' => ['nullable', 'string', 'max:180'],
            'programme.*.description' => ['nullable', 'string', 'max:500'],
            'gallery_images' => ['nullable', 'array', 'max:12'],
            'gallery_images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'gallery_remove' => ['nullable', 'array'],
            'gallery_remove.*' => ['integer'],
        ]);

        foreach ($request->input('programme', []) as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $startsAt = $this->normalizeClock($row['starts_at'] ?? null);
            if (! $startsAt) {
                throw ValidationException::withMessages([
                    'programme' => ['Each programme item needs a start time, for example 08:00.'],
                ]);
            }
            $endsAt = $this->normalizeClock($row['ends_at'] ?? null);
            if ($endsAt && $endsAt < $startsAt) {
                throw ValidationException::withMessages([
                    'programme' => ['Programme end time must be after the start time.'],
                ]);
            }
        }
    }

    private function syncSpeakers(Event $event, Request $request, bool $isUpdate = false): void
    {
        $data = $request->validate([
            'speakers' => ['nullable', 'array', 'max:20'],
            'speakers.*.id' => ['nullable', 'integer'],
            'speakers.*.name' => ['nullable', 'string', 'max:120'],
            'speakers.*.role' => ['nullable', 'string', 'max:120'],
            'speakers.*.bio' => ['nullable', 'string', 'max:500'],
            'speakers.*.photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        $keepIds = [];

        foreach ($data['speakers'] ?? [] as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $photoFile = $request->file("speakers.{$index}.photo");
            $existing = null;
            if ($isUpdate && ! empty($row['id'])) {
                $existing = EventSpeaker::query()
                    ->where('event_id', $event->id)
                    ->whereKey($row['id'])
                    ->first();
            }

            $photoPath = $existing?->getRawOriginal('photo');
            if ($photoFile) {
                if ($photoPath) {
                    PublicUpload::delete($photoPath);
                }
                $photoPath = $this->storeEventImage($photoFile, 'images/events/speakers');
            }

            $payload = [
                'name' => $name,
                'role' => trim((string) ($row['role'] ?? '')) ?: null,
                'bio' => trim((string) ($row['bio'] ?? '')) ?: null,
                'photo' => $photoPath,
                'sort_order' => count($keepIds),
            ];

            if ($existing) {
                $existing->update($payload);
                $keepIds[] = $existing->id;
            } else {
                $created = EventSpeaker::query()->create([
                    'event_id' => $event->id,
                    ...$payload,
                ]);
                $keepIds[] = $created->id;
            }
        }

        $query = EventSpeaker::query()->where('event_id', $event->id);
        if ($keepIds !== []) {
            $query->whereNotIn('id', $keepIds);
        }
        $query->get()->each->delete();
    }

    private function syncProgramme(Event $event, Request $request, bool $isUpdate = false): void
    {
        $data = $request->validate([
            'programme' => ['nullable', 'array', 'max:40'],
            'programme.*.id' => ['nullable', 'integer'],
            'programme.*.starts_at' => ['nullable', 'string', 'max:8'],
            'programme.*.ends_at' => ['nullable', 'string', 'max:8'],
            'programme.*.title' => ['nullable', 'string', 'max:180'],
            'programme.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        $keepIds = [];

        foreach ($data['programme'] ?? [] as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $startsAt = $this->normalizeClock($row['starts_at'] ?? null);
            if (! $startsAt) {
                throw ValidationException::withMessages([
                    'programme' => ['Each programme item needs a start time, for example 08:00.'],
                ]);
            }

            $endsAt = $this->normalizeClock($row['ends_at'] ?? null);
            if ($endsAt && $endsAt < $startsAt) {
                throw ValidationException::withMessages([
                    'programme' => ['Programme end time must be after the start time.'],
                ]);
            }

            $payload = [
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'title' => $title,
                'description' => trim((string) ($row['description'] ?? '')) ?: null,
                'sort_order' => count($keepIds),
            ];

            $existing = null;
            if ($isUpdate && ! empty($row['id'])) {
                $existing = EventProgrammeItem::query()
                    ->where('event_id', $event->id)
                    ->whereKey($row['id'])
                    ->first();
            }

            if ($existing) {
                $existing->update($payload);
                $keepIds[] = $existing->id;
            } else {
                $created = EventProgrammeItem::query()->create([
                    'event_id' => $event->id,
                    ...$payload,
                ]);
                $keepIds[] = $created->id;
            }
        }

        $query = EventProgrammeItem::query()->where('event_id', $event->id);
        if ($keepIds !== []) {
            $query->whereNotIn('id', $keepIds);
        }
        $query->delete();
    }

    private function syncGallery(Event $event, Request $request, bool $isUpdate = false): void
    {
        $request->validate([
            'gallery_images' => ['nullable', 'array', 'max:12'],
            'gallery_images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'gallery_remove' => ['nullable', 'array'],
            'gallery_remove.*' => ['integer'],
        ]);

        $removeIds = collect($request->input('gallery_remove', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        if ($isUpdate && $removeIds !== []) {
            EventGalleryImage::query()
                ->where('event_id', $event->id)
                ->whereIn('id', $removeIds)
                ->get()
                ->each->delete();
        }

        $remaining = EventGalleryImage::query()->where('event_id', $event->id)->count();
        $uploads = $request->file('gallery_images', []);
        if (! is_array($uploads)) {
            $uploads = $uploads ? [$uploads] : [];
        }
        $uploads = array_values(array_filter($uploads));

        if ($remaining + count($uploads) > 12) {
            throw ValidationException::withMessages([
                'gallery_images' => ['You can add up to 12 gallery photos per event.'],
            ]);
        }

        $sort = (int) EventGalleryImage::query()->where('event_id', $event->id)->max('sort_order');

        foreach ($uploads as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $sort++;
            EventGalleryImage::query()->create([
                'event_id' => $event->id,
                'path' => $this->storeEventImage($file, 'images/events/gallery'),
                'sort_order' => $sort,
            ]);
        }
    }

    private function storeEventImage(UploadedFile $file, string $directory): string
    {
        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        return PublicUpload::store($file, $directory, $filename);
    }

    private function normalizeClock(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})/', $value, $matches) !== 1) {
            return null;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];
        if ($hour > 23 || $minute > 59) {
            return null;
        }

        return sprintf('%02d:%02d:00', $hour, $minute);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $i = 1;
        while (Event::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
