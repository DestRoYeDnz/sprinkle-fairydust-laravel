<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryImageController extends Controller
{
    /**
     * Return active gallery/design images in display order.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'collection' => ['nullable', 'in:gallery,designs,events'],
        ]);

        $collection = $validated['collection'] ?? 'gallery';

        $images = GalleryImage::query()
            ->select(['id', 'url', 'alt_text', 'title', 'description', 'sort_order', 'collection', 'event_id'])
            ->with([
                'event' => fn ($query) => $query->select([
                    'id',
                    'name',
                    'type',
                    'visibility',
                    'address',
                    'date',
                    'start_time',
                    'end_time',
                    'description',
                ]),
            ])
            ->where('is_active', true)
            ->when($collection === 'gallery', function ($query) {
                $query->where(function ($galleryQuery) {
                    $galleryQuery->where('collection', 'gallery')
                        ->orWhere(function ($eventQuery) {
                            $eventQuery->where('collection', 'events')
                                ->whereNotNull('event_id')
                                ->whereHas('event', fn ($relatedEventQuery) => $relatedEventQuery->where('visibility', 'public'));
                        });
                });
            })
            ->when($collection === 'designs', fn ($query) => $query->where('collection', 'designs'))
            ->when($collection === 'events', function ($query) {
                $query->where('collection', 'events')
                    ->whereNotNull('event_id')
                    ->whereHas('event', fn ($relatedEventQuery) => $relatedEventQuery->where('visibility', 'public'));
            })
            ->orderByRaw("case when collection = 'gallery' then 0 else 1 end")
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (GalleryImage $image): array => [
                'id' => $image->id,
                'url' => $image->url,
                'alt_text' => $image->alt_text,
                'title' => $image->title,
                'description' => $image->description,
                'sort_order' => $image->sort_order,
                'collection' => $image->collection,
                'event_id' => $image->event_id,
                'event' => $image->event ? [
                    'id' => $image->event->id,
                    'name' => $image->event->name,
                    'type' => $image->event->type,
                    'visibility' => $image->event->visibility,
                    'address' => $image->event->address,
                    'date' => $image->event->date?->toDateString(),
                    'start_time' => $image->event->start_time,
                    'end_time' => $image->event->end_time,
                    'description' => $image->event->description,
                ] : null,
            ])
            ->values();

        return response()->json($images)->header('Cache-Control', 'no-cache');
    }
}
