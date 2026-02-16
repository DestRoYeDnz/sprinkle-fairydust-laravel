<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminUploadController extends Controller
{
    /**
     * Upload an image to local public storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'image', 'max:10240'],
            'collection' => ['nullable', 'in:gallery,designs,events'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $collection = $validated['collection'] ?? 'gallery';
        $path = $validated['file']->store("sprinkle/{$collection}", 'public');
        $nextSortOrder = ((int) GalleryImage::query()
            ->where('collection', $collection)
            ->max('sort_order')) + 1;

        $image = GalleryImage::query()->create([
            'collection' => $collection,
            'url' => Storage::url($path),
            'alt_text' => $validated['alt_text'] ?? null,
            'sort_order' => $nextSortOrder,
            'is_active' => true,
            'uploaded_by' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'url' => $image->url,
            'collection' => $image->collection,
            'id' => $image->id,
        ]);
    }
}
