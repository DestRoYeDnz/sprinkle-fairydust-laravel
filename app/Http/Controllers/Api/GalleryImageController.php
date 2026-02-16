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
            'collection' => ['nullable', 'in:gallery,designs'],
        ]);

        $collection = $validated['collection'] ?? 'gallery';

        $images = GalleryImage::query()
            ->select(['id', 'url', 'alt_text', 'sort_order', 'collection'])
            ->where('collection', $collection)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return response()->json($images)->header('Cache-Control', 'no-cache');
    }
}
