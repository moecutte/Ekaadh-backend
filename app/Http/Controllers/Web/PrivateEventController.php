<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\InvitationDesign;
use App\Models\Order;
use App\Support\InvitationPreview;
use App\Services\OrderService;
use App\Services\PrivateEventService;
use App\Services\Payments\WaafiPayGateway;
use App\Support\PaymentMessage;
use App\Support\Phone;
use App\Support\PublicUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PrivateEventController extends Controller
{
    public function __construct(private PrivateEventService $privateEvents) {}

    public function index(Request $request): View
    {
        $user = $this->customer();

        $events = Event::query()
            ->with(['ticketTypes', 'invitationDesign'])
            ->where('owner_user_id', $user->id)
            ->where('is_private', true)
            ->latest()
            ->paginate(12);

        return view('private-events.index', compact('events'));
    }

    public function create(): View
    {
        $this->customer();

        return view('private-events.create', [
            'unitPrice' => PrivateEventService::unitPrice(),
            'premiumSurcharge' => PrivateEventService::premiumDesignSurcharge(),
            'serviceFee' => PrivateEventService::serviceFee(),
            'maxTickets' => PrivateEventService::maxTickets(),
            'cities' => City::activeNames(),
            'categories' => Category::activeOptionsForPrivate(),
            'standardDesigns' => \App\Support\TicketDesigns::standard(),
            'premiumDesigns' => \App\Support\TicketDesigns::premium(),
            'defaultDesign' => \App\Support\TicketDesigns::defaultId(),
            'allDesigns' => array_values(\App\Support\TicketDesigns::all()),
            'pickerPreviews' => \App\Models\InvitationDesign::query()
                ->active()
                ->with('fields')
                ->ordered()
                ->get()
                ->mapWithKeys(fn ($design) => [$design->id => \App\Support\InvitationPreview::make($design)]),
        ]);
    }

    public function invitationPreview(Request $request): View
    {
        $this->customer();

        $design = InvitationDesign::query()
            ->with('fields')
            ->findOrFail($request->integer('invitation_design_id'));

        abort_unless($design->is_active, 404);

        $fields = $request->input('fields', []);
        if (! is_array($fields)) {
            $fields = [];
        }

        $preview = InvitationPreview::make($design, $fields, [
            'event_date' => $request->input('event_date'),
            'event_time' => $request->input('event_time'),
            'venue' => $request->input('venue'),
            'address' => $request->input('address'),
            'city' => $request->input('city'),
            'guest_name' => $request->input('guest_name', ''),
        ]);

        return view('invitations.preview-frame', $preview + [
            'showQr' => $request->boolean('show_qr', false),
            'withEnvelope' => $request->boolean('envelope', true),
            'autoOpen' => $request->boolean('auto_open', true),
            'compact' => $request->boolean('compact', false),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->customer();
        $max = PrivateEventService::maxTickets();
        $categoryIds = Category::activePrivateChildIds();

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100', Rule::in(City::activeNames())],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$max],
            'ticket_label' => ['nullable', 'string', 'max:120'],
            'ticket_design' => ['nullable', 'string', Rule::in(\App\Support\TicketDesigns::ids())],
            'invitation_design_id' => ['required', 'integer', 'exists:invitation_designs,id'],
            'invitation_field_values' => ['nullable', 'array'],
            'invitation_field_values.*' => ['nullable', 'string', 'max:500'],
            'event_date' => ['nullable', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'private_event_category_id' => ['required', 'integer', Rule::in($categoryIds)],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $data['cover_image'] = $this->storeCoverImage($request->file('cover_image'));

        try {
            $result = $this->privateEvents->createWithCheckout($user, $data);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('private-events.pay', $result['event'])
            ->with('success', 'Private event created. Pay to unlock invitations.');
    }

    public function show(Event $event): View|RedirectResponse
    {
        $user = $this->customer();
        $this->authorizeOwner($event, $user);

        if ($event->status === 'draft') {
            return redirect()->route('private-events.pay', $event);
        }

        $event->load(['ticketTypes', 'invitationDesign']);
        $remaining = $event->ticketTypes->sum(fn ($t) => $t->remaining());
        $sold = $event->ticketTypes->sum('quantity_sold');
        $capacity = $event->ticketTypes->sum('quantity_available');

        return view('private-events.show', compact('event', 'remaining', 'sold', 'capacity'));
    }

    public function payForm(Event $event): View
    {
        $user = $this->customer();
        $this->authorizeOwner($event, $user);

        $order = Order::query()
            ->with(['items.ticketType', 'event'])
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('source', 'private_event')
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (! $order && $event->status === 'published') {
            return redirect()->route('private-events.show', $event);
        }

        if (! $order) {
            abort(404, 'No pending payment found for this private event.');
        }

        $event->load('ticketTypes');
        $invitePreview = null;
        if ($event->invitation_design_id) {
            $design = InvitationDesign::query()->with('fields')->find($event->invitation_design_id);
            if ($design) {
                $invitePreview = InvitationPreview::make(
                    $design,
                    is_array($event->invitation_field_values) ? $event->invitation_field_values : [],
                    [
                        'event_date' => $event->event_date?->format('Y-m-d'),
                        'event_time' => $event->event_time ? substr((string) $event->event_time, 0, 5) : null,
                        'venue' => $event->venue,
                        'address' => $event->address,
                        'city' => $event->city,
                    ]
                );
            }
        }

        return view('private-events.pay', [
            'event' => $event,
            'order' => $order,
            'invitePreview' => $invitePreview,
            'allowForceFail' => OrderService::allowsForceFail(),
            'waafiSandbox' => (bool) config('waafipay.sandbox'),
            'waafiTestWallets' => config('waafipay.test_wallets', []),
        ]);
    }

    public function pay(Request $request, Event $event): RedirectResponse
    {
        $user = $this->customer();
        $this->authorizeOwner($event, $user);

        $data = $request->validate([
            'payment_method' => ['required', 'in:waafipay'],
            'force_fail' => ['sometimes', 'boolean'],
            'wallet_pin' => ['nullable', 'string', 'max:8'],
            'buyer_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $order = Order::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('source', 'private_event')
            ->where('status', 'pending')
            ->latest()
            ->firstOrFail();

        $walletPin = WaafiPayGateway::sandboxPin($data['wallet_pin'] ?? null);
        if (config('waafipay.sandbox') && $walletPin === null) {
            return back()->withErrors([
                'wallet_pin' => WaafiPayGateway::sandboxPinError($data['wallet_pin'] ?? null),
            ]);
        }

        $chargePhone = Phone::normalize($user->phone);
        if (config('waafipay.sandbox')) {
            $chargePhone = Phone::normalize($data['buyer_phone'] ?? '');
            if ($chargePhone === '') {
                return back()->withErrors([
                    'buyer_phone' => __('ui.sandbox_charge_phone_required'),
                ]);
            }
        }

        try {
            $order = $this->privateEvents->payCapacityOrder(
                $order,
                $data['payment_method'],
                $chargePhone,
                OrderService::allowsForceFail() && $request->boolean('force_fail'),
                $walletPin
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        if ($order->status === 'paid') {
            return redirect()
                ->route('private-events.invitations.index', $event)
                ->with('success', 'Payment successful. Send invitations to your guests.');
        }

        if ($order->status === 'pending') {
            return redirect()
                ->route('private-events.pay', $event)
                ->with('success', __('ui.payment_pending_hint'));
        }

        $order->loadMissing('payment');

        return redirect()
            ->route('private-events.pay', $event)
            ->with('error', PaymentMessage::forOrder($order));
    }

    public function addCapacityForm(Event $event): View
    {
        $user = $this->customer();
        $this->authorizeOwner($event, $user);
        abort_unless($event->status === 'published', 404);

        return view('private-events.add-capacity', [
            'event' => $event->load('ticketTypes'),
            'unitPrice' => (float) ($event->ticketTypes->first()?->price ?? PrivateEventService::unitPrice()),
            'serviceFee' => PrivateEventService::serviceFee(),
            'maxTickets' => PrivateEventService::maxTickets(),
        ]);
    }

    public function addCapacity(Request $request, Event $event): RedirectResponse
    {
        $user = $this->customer();
        $this->authorizeOwner($event, $user);
        $max = PrivateEventService::maxTickets();

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$max],
        ]);

        try {
            $result = $this->privateEvents->addCapacityCheckout($event, $user, (int) $data['quantity']);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('private-events.pay', $result['event'])
            ->with('success', 'Pay to add more invitation tickets.');
    }

    private function customer()
    {
        $user = auth()->user();
        abort_unless($user && $user->isCustomer(), 403);

        return $user;
    }

    private function authorizeOwner(Event $event, $user): void
    {
        abort_unless($event->is_private && $event->owner_user_id === $user->id, 403);
    }

    private function storeCoverImage(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        return PublicUpload::store($file, 'images/events', $filename);
    }
}
