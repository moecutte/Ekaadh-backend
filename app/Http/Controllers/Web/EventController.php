<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function home(): View
    {
        $featured = Event::query()
            ->publicListing()
            ->upcoming()
            ->with('ticketTypes')
            ->where('is_featured', true)
            ->orderBy('event_date')
            ->take(3)
            ->get();

        $upcoming = Event::query()
            ->publicListing()
            ->upcoming()
            ->with('ticketTypes')
            ->orderBy('event_date')
            ->take(6)
            ->get();

        $homeEventsWhen = 'upcoming';
        if ($upcoming->isEmpty()) {
            $upcoming = Event::query()
                ->publicListing()
                ->past()
                ->with('ticketTypes')
                ->orderByDesc('event_date')
                ->orderByDesc('event_time')
                ->take(6)
                ->get();
            $homeEventsWhen = 'past';
        }

        $categories = collect(Category::activeNames());
        $cities = collect(City::activeNames());

        return view('events.home', compact('featured', 'upcoming', 'categories', 'cities', 'homeEventsWhen'));
    }

    public function index(Request $request): View
    {
        $when = Event::listingWhen($request->string('when')->toString());

        $query = Event::query()
            ->publicListing()
            ->with('ticketTypes');

        if ($when === 'past') {
            $query->past()->orderByDesc('event_date')->orderByDesc('event_time');
        } else {
            $query->upcoming()->orderBy('event_date')->orderBy('event_time');
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('venue', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->trim()->toString()) {
            $query->where('category', $category);
        }

        if ($city = $request->string('city')->trim()->toString()) {
            $query->where('city', $city);
        }

        $events = $query->paginate(12)->withQueryString();
        $categories = collect(Category::activeNames());
        $cities = collect(City::activeNames());

        return view('events.index', compact('events', 'categories', 'cities', 'when'));
    }

    public function show(string $slug): View
    {
        $event = Event::query()
            ->with(['organizer.user', 'ticketTypes', 'speakers', 'programmeItems', 'galleryImages'])
            ->where('slug', $slug)
            ->when(
                ! auth()->user()?->isAdmin(),
                fn ($q) => $q->publicListing(),
                fn ($q) => $q->where('is_private', false)
            )
            ->firstOrFail();

        $related = Event::query()
            ->publicListing()
            ->with('ticketTypes')
            ->where('id', '!=', $event->id)
            ->where(function ($q) use ($event) {
                $q->where('category', $event->category)
                    ->orWhere('city', $event->city);
            })
            ->orderBy('event_date')
            ->take(3)
            ->get();

        return view('events.show', compact('event', 'related'));
    }
}
