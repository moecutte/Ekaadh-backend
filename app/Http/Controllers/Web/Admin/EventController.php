<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\OrganizerProfile;
use App\Services\InvitationService;
use App\Services\PanelNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(private InvitationService $invitations) {}

    public function index(Request $request): View
    {
        $query = Event::query()
            ->with(['organizer', 'owner', 'privateEventCategory', 'ticketTypes', 'package'])
            ->latest();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('venue', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhereHas('organizer', fn ($o) => $o->where('business_name', 'like', "%{$search}%"))
                    ->orWhereHas('owner', function ($o) use ($search) {
                        $o->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $type = $request->string('type')->toString();
        if (! in_array($type, ['public', 'private'], true)) {
            $type = 'public';
        }

        if ($type === 'private') {
            $query->where('is_private', true);
        } else {
            $query->where('is_private', false);
        }

        if ($category = $request->string('category')->toString()) {
            if ($type === 'private') {
                $query->whereHas('privateEventCategory', fn ($c) => $c->where('name', $category));
            } else {
                $query->where('category', $category);
            }
        }

        if ($type === 'public' && ($organizerId = $request->integer('organizer_id'))) {
            $query->where('organizer_id', $organizerId);
        }

        $featured = $request->input('featured');
        if ($type === 'public' && ($featured === '0' || $featured === '1')) {
            $query->where('is_featured', $featured === '1');
        }

        if ($from = $request->string('date_from')->toString()) {
            $query->whereDate('event_date', '>=', $from);
        }

        if ($to = $request->string('date_to')->toString()) {
            $query->whereDate('event_date', '<=', $to);
        }

        $perPage = (int) $request->integer('per_page', 15);
        if (! in_array($perPage, [10, 15, 20, 50], true)) {
            $perPage = 15;
        }

        $events = $query->paginate($perPage)->withQueryString();

        $filterOptions = [
            'organizers' => OrganizerProfile::query()->orderBy('business_name')->get(['id', 'business_name']),
            'categories' => $type === 'private'
                ? collect(Category::activeOptionsForPrivate())->pluck('name')->all()
                : Category::activeNames(),
        ];

        $tabCounts = [
            'public' => Event::query()->where('is_private', false)->count(),
            'private' => Event::query()->where('is_private', true)->count(),
        ];

        $filtersActive = collect($request->only([
            'q', 'status', 'category', 'organizer_id', 'featured', 'date_from', 'date_to', 'per_page',
        ]))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();

        return view('admin.events.index', compact('events', 'filterOptions', 'filtersActive', 'perPage', 'type', 'tabCounts'));
    }

    public function approve(Event $event): RedirectResponse
    {
        $event->load('package');
        if ($event->needsPackagePayment()) {
            return back()->with('error', "Cannot publish {$event->title} until the organizer pays the free-event package.");
        }

        $event->update(['status' => 'published']);
        $flushed = $this->invitations->flushPending($event->fresh(['ticketTypes']));
        $extra = $flushed['created'] > 0
            ? " Sent {$flushed['created']} complimentary invitation(s)."
            : ($flushed['error'] ? ' Complimentary invitations could not be sent: '.$flushed['error'] : '');

        $this->notifyOrganizerEventStatus($event, 'published');

        return back()->with('success', "Published {$event->title}.".$extra);
    }

    public function reject(Event $event): RedirectResponse
    {
        $event->update(['status' => 'cancelled']);
        $this->notifyOrganizerEventStatus($event, 'cancelled');

        return back()->with('success', "Cancelled {$event->title}.");
    }

    public function toggleFeatured(Event $event): RedirectResponse
    {
        $event->update(['is_featured' => ! $event->is_featured]);

        return back()->with('success', $event->is_featured ? 'Event featured.' : 'Feature removed.');
    }

    public function updateStatus(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:draft,pending_review,published,completed,cancelled'],
        ]);

        $event->load('package');
        if ($data['status'] === 'published' && $event->needsPackagePayment()) {
            return back()->with('error', "Cannot publish {$event->title} until the organizer pays the free-event package.");
        }

        $event->update(['status' => $data['status']]);
        $extra = '';
        if ($data['status'] === 'published') {
            $flushed = $this->invitations->flushPending($event->fresh(['ticketTypes']));
            if ($flushed['created'] > 0) {
                $extra = " Sent {$flushed['created']} complimentary invitation(s).";
            } elseif ($flushed['error']) {
                $extra = ' Complimentary invitations could not be sent: '.$flushed['error'];
            }
            $this->notifyOrganizerEventStatus($event, 'published');
        } elseif (in_array($data['status'], ['cancelled', 'completed'], true)) {
            $this->notifyOrganizerEventStatus($event, $data['status']);
        }

        return back()->with('success', 'Event status updated.'.$extra);
    }

    private function notifyOrganizerEventStatus(Event $event, string $status): void
    {
        $user = $event->organizer?->user;
        if (! $user) {
            $event->loadMissing('organizer.user');
            $user = $event->organizer?->user;
        }
        if (! $user) {
            return;
        }

        [$title, $body, $kind] = match ($status) {
            'published' => ['Event published', "{$event->title} is now live.", 'event_published'],
            'completed' => ['Event completed', "{$event->title} was marked completed.", 'event_completed'],
            default => ['Event cancelled', "{$event->title} was cancelled.", 'event_cancelled'],
        };

        app(PanelNotifier::class)->toUser(
            $user,
            $title,
            $body,
            $kind,
            route('organizer.events.index'),
            ['event_id' => (string) $event->id],
        );
    }
}
