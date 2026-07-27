<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Event::query()
            ->publicListing()
            ->with(['organizer', 'ticketTypes'])
            ->orderBy('event_date')
            ->orderBy('event_time');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('venue', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->trim()->toString()) {
            if (strcasecmp($category, 'All') !== 0) {
                $query->where('category', $category);
            }
        }

        if ($city = $request->string('city')->trim()->toString()) {
            $query->where('city', $city);
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $events = $query->paginate(
            perPage: min((int) $request->integer('per_page', 20), 50)
        );

        return EventResource::collection($events)->additional([
            'filters' => [
                'categories' => Category::activeNames(),
                'cities' => City::activeNames(),
            ],
        ]);
    }

    public function show(string $idOrSlug): EventResource
    {
        $event = Event::query()
            ->publicListing()
            ->with(['organizer', 'ticketTypes'])
            ->when(
                ctype_digit($idOrSlug),
                fn ($q) => $q->where('id', (int) $idOrSlug),
                fn ($q) => $q->where('slug', $idOrSlug)
            )
            ->firstOrFail();

        return new EventResource($event);
    }
}
