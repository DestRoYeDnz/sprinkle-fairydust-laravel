<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestimonialManagementController extends Controller
{
    /**
     * List all testimonials for admin management.
     */
    public function index(): JsonResponse
    {
        $testimonials = Testimonial::query()
            ->select(['id', 'name', 'testimonial', 'urls', 'is_approved', 'approved_at', 'created_at', 'updated_at'])
            ->latest('created_at')
            ->get();

        return response()->json($testimonials)->header('Cache-Control', 'no-cache');
    }

    /**
     * Store a new testimonial.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateTestimonial($request);

        $payload = $this->payload($validated);
        $isApproved = (bool) ($validated['is_approved'] ?? true);
        $payload['is_approved'] = $isApproved;
        $payload['approved_at'] = $isApproved ? now() : null;

        $testimonial = Testimonial::query()->create($payload);

        return response()->json([
            'success' => true,
            'testimonial' => $testimonial,
        ]);
    }

    /**
     * Update a testimonial.
     */
    public function update(Request $request, Testimonial $testimonial): JsonResponse
    {
        $validated = $this->validateTestimonial($request);

        $payload = $this->payload($validated);

        if (array_key_exists('is_approved', $validated)) {
            $isApproved = (bool) $validated['is_approved'];
            $payload['is_approved'] = $isApproved;
            $payload['approved_at'] = $isApproved
                ? ($testimonial->approved_at ?? now())
                : null;
        }

        $testimonial->update($payload);

        return response()->json([
            'success' => true,
            'testimonial' => $testimonial->fresh(),
        ]);
    }

    /**
     * Delete a testimonial.
     */
    public function destroy(Testimonial $testimonial): JsonResponse
    {
        $testimonial->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Validate testimonial payload.
     *
     * @return array<string, mixed>
     */
    private function validateTestimonial(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'testimonial' => ['required', 'string'],
            'urls' => ['nullable', 'array', 'max:12'],
            'urls.*' => ['string', 'max:2048'],
            'is_approved' => ['sometimes', 'boolean'],
        ]);
    }

    /**
     * Build model payload from validated data.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated): array
    {
        $urls = collect($validated['urls'] ?? [])
            ->map(fn ($url) => trim((string) $url))
            ->filter()
            ->values()
            ->all();

        return [
            'name' => $validated['name'],
            'testimonial' => $validated['testimonial'],
            'urls' => $urls,
        ];
    }
}
