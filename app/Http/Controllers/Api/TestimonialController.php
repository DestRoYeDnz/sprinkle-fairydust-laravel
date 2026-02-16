<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestimonialController extends Controller
{
    /**
     * Paginated testimonials endpoint.
     */
    public function index(Request $request): JsonResponse
    {
        $page = max((int) $request->query('page', 1), 1);
        $limit = min(max((int) $request->query('limit', 5), 1), 20);

        $paginator = Testimonial::query()
            ->select(['id', 'name', 'testimonial', 'urls', 'created_at'])
            ->where('is_approved', true)
            ->latest('created_at')
            ->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'testimonials' => $paginator->items(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'limit' => $paginator->perPage(),
            'totalPages' => $paginator->lastPage(),
        ]);
    }

    /**
     * Store a testimonial.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'testimonial' => ['required', 'string'],
            'urls' => ['nullable', 'array', 'max:3'],
            'urls.*' => ['string', 'max:2048'],
        ]);

        $testimonial = Testimonial::query()->create([
            'name' => $validated['name'],
            'testimonial' => $validated['testimonial'],
            'urls' => $validated['urls'] ?? [],
            'is_approved' => false,
            'approved_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thanks! Your testimonial was submitted and is waiting for admin approval.',
            'urls' => $testimonial->urls,
        ]);
    }

    /**
     * Upload a testimonial image to local public storage.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $path = $validated['file']->store('sprinkle/testimonials', 'public');

        return response()->json([
            'success' => true,
            'url' => Storage::url($path),
        ]);
    }
}
