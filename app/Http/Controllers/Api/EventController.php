<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Models\Event;
use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    /**
     * Return public events in chronological order.
     */
    public function index(): JsonResponse
    {
        return $this->eventResponse(
            Event::query()->where('visibility', 'public'),
        );
    }

    /**
     * Return public and private events for the admin area.
     */
    public function adminIndex(): JsonResponse
    {
        return $this->eventResponse(Event::query());
    }

    /**
     * Store a new event from the admin form.
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        Event::query()->create($request->validated());

        return response()->json(['success' => true]);
    }

    /**
     * Build the event response payload.
     */
    protected function eventResponse(Builder $query): JsonResponse
    {
        $events = $query
            ->select(['id', 'name', 'type', 'visibility', 'address', 'date', 'start_time', 'end_time', 'description', 'image_url'])
            ->with([
                'photos' => fn ($photoQuery) => $photoQuery->select([
                    'id',
                    'event_id',
                    'url',
                    'alt_text',
                    'sort_order',
                    'collection',
                ]),
            ])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(function (Event $event): array {
                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'type' => $event->type,
                    'visibility' => $event->visibility,
                    'address' => $event->address,
                    'date' => $event->date?->toDateString(),
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'description' => $event->description,
                    'image_url' => $event->image_url,
                    'photos' => $event->photos->map(fn (GalleryImage $photo): array => [
                        'id' => $photo->id,
                        'event_id' => $photo->event_id,
                        'url' => $photo->url,
                        'alt_text' => $photo->alt_text,
                        'sort_order' => $photo->sort_order,
                        'collection' => $photo->collection,
                    ])->values()->all(),
                ];
            })
            ->values();

        return response()->json($events)->header('Cache-Control', 'no-cache');
    }
}
