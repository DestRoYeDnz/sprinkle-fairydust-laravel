<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGalleryImageUploadRequest;
use App\Models\Event;
use App\Models\GalleryImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class AdminUploadController extends Controller
{
    /**
     * Upload an image to local public storage.
     */
    public function store(StoreGalleryImageUploadRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $event = isset($validated['event_id'])
            ? Event::query()->find($validated['event_id'])
            : null;

        if ($event instanceof Event && ! $event->date?->lt(today())) {
            return response()->json([
                'message' => 'Event photos can only be added to past events.',
            ], 422);
        }

        $collection = $validated['collection'] ?? 'gallery';
        $collection = $event instanceof Event ? 'events' : $collection;
        $title = isset($validated['title']) ? trim((string) $validated['title']) : null;
        $description = isset($validated['description']) ? trim((string) $validated['description']) : null;
        $path = $validated['file']->store("sprinkle/{$collection}", 'public');
        $sortOrderQuery = GalleryImage::query()->where('collection', $collection);

        if ($event instanceof Event) {
            $sortOrderQuery->where('event_id', $event->id);
        } else {
            $sortOrderQuery->whereNull('event_id');
        }

        $nextSortOrder = ((int) $sortOrderQuery->max('sort_order')) + 1;

        $image = GalleryImage::query()->create([
            'collection' => $collection,
            'url' => Storage::url($path),
            'alt_text' => $validated['alt_text'] ?? $title ?? ($event?->name ? "{$event->name} event photo" : null),
            'title' => $title,
            'description' => $description,
            'sort_order' => $nextSortOrder,
            'is_active' => true,
            'uploaded_by' => $request->user()?->id,
            'event_id' => $event?->id,
        ]);

        return response()->json([
            'success' => true,
            'url' => $image->url,
            'collection' => $image->collection,
            'id' => $image->id,
            'event_id' => $image->event_id,
            'title' => $image->title,
            'description' => $image->description,
        ]);
    }
}
