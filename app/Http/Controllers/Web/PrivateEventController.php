<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PrivateEventService;
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

        return view('private-events.pay', [
            'event' => $event->load('ticketTypes'),
            'order' => $order,
            'allowForceFail' => OrderService::allowsForceFail(),
        ]);
    }

    public function pay(Request $request, Event $event): RedirectResponse
    {
        $user = $this->customer();
        $this->authorizeOwner($event, $user);

        $data = $request->validate([
            'payment_method' => ['required', 'in:zaad,edahab'],
            'force_fail' => ['sometimes', 'boolean'],
        ]);

        $order = Order::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('source', 'private_event')
            ->where('status', 'pending')
            ->latest()
            ->firstOrFail();

        try {
            $order = $this->privateEvents->payCapacityOrder(
                $order,
                $data['payment_method'],
                $user->phone,
                OrderService::allowsForceFail() && $request->boolean('force_fail')
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        if ($order->status === 'paid') {
            return redirect()
                ->route('private-events.invitations.index', $event)
                ->with('success', 'Payment successful. Send invitations to your guests.');
        }

        return redirect()
            ->route('private-events.pay', $event)
            ->with('error', 'Payment could not be completed. Try again.');
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
