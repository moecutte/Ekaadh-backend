<?php

namespace App\Http\Controllers\Web\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrganizerProfile;
use App\Models\TicketType;
use App\Support\PublicUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $profile = auth()->user()->organizerProfile;

        $baseQuery = Event::query()->where('organizer_id', $profile->id);

        $query = (clone $baseQuery)->with('ticketTypes');

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
        return view('organizer.events.form', [
            'event' => new Event(['status' => 'draft', 'category' => Category::activeNames()[0] ?? 'Music']),
            'categories' => Category::activeNames(),
            'cities' => City::activeNames(),
            'ticketTypes' => collect([
                ['name' => 'General Admission', 'price' => 15, 'quantity_available' => 100, 'max_per_order' => 5, 'description' => ''],
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = auth()->user()->organizerProfile->load('package');
        $data = $this->validated($request);
        $tickets = $this->validatedTickets($request);

        if ($error = $this->packageLimitError($profile, $tickets)) {
            return back()->withInput()->with('error', $error);
        }

        $status = $request->input('action') === 'publish' ? 'pending_review' : 'draft';

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
            'status' => $status,
        ]);

        $this->syncTicketTypes($event, $tickets);

        return redirect()
            ->route('organizer.events.index')
            ->with('success', $status === 'draft' ? 'Event saved as draft.' : 'Event submitted for admin review.');
    }

    public function edit(Event $event): View
    {
        $this->authorizeEvent($event);

        return view('organizer.events.form', [
            'event' => $event,
            'categories' => $this->categoryOptionsFor($event),
            'cities' => $this->cityOptionsFor($event),
            'ticketTypes' => $event->ticketTypes->map(fn (TicketType $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'description' => $t->description,
                'price' => $t->price,
                'quantity_available' => $t->quantity_available,
                'max_per_order' => $t->max_per_order,
            ]),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeEvent($event);
        $profile = auth()->user()->organizerProfile->load('package');
        $data = $this->validated($request, $event);
        $tickets = $this->validatedTickets($request);

        if ($error = $this->packageLimitError($profile, $tickets, $event)) {
            return back()->withInput()->with('error', $error);
        }

        $status = $event->status;
        if ($request->input('action') === 'publish') {
            $status = 'pending_review';
        }
        if ($request->input('action') === 'draft') {
            $status = 'draft';
        }

        $coverImage = $event->getRawOriginal('cover_image');
        if ($request->hasFile('cover_image')) {
            $this->deleteLocalCoverImage($coverImage);
            $coverImage = $this->storeCoverImage($request->file('cover_image'));
        }

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
            'status' => $status,
        ]);

        $this->syncTicketTypes($event, $tickets, true);

        $message = match ($status) {
            'draft' => 'Event saved as draft.',
            'pending_review' => 'Event submitted for admin review.',
            default => 'Event updated.',
        };

        return redirect()
            ->route('organizer.events.index')
            ->with('success', $message);
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
        abort_unless($event->organizer_id === auth()->user()->organizerProfile->id, 403);
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
     * @return array<int, array<string, mixed>>
     */
    private function validatedTickets(Request $request): array
    {
        $data = $request->validate([
            'tickets' => ['required', 'array', 'min:1'],
            'tickets.*.id' => ['nullable', 'integer'],
            'tickets.*.name' => ['required', 'string', 'max:120'],
            'tickets.*.description' => ['nullable', 'string', 'max:255'],
            'tickets.*.price' => ['required', 'numeric', 'min:0'],
            'tickets.*.quantity_available' => ['required', 'integer', 'min:1'],
            'tickets.*.max_per_order' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        return $data['tickets'];
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
