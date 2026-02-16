<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteManagementController extends Controller
{
    /**
     * List all quotes for admin management.
     */
    public function index(): JsonResponse
    {
        $quotes = Quote::query()
            ->select([
                'id',
                'name',
                'email',
                'event_type',
                'event_date',
                'address',
                'start_time',
                'end_time',
                'total_hours',
                'details',
                'created_at',
                'updated_at',
            ])
            ->latest('created_at')
            ->get();

        return response()->json($quotes)->header('Cache-Control', 'no-cache');
    }

    /**
     * Store a new quote record.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateQuote($request);

        $quote = Quote::query()->create($this->payload($validated));

        return response()->json([
            'success' => true,
            'quote' => $quote,
        ]);
    }

    /**
     * Update an existing quote record.
     */
    public function update(Request $request, Quote $quote): JsonResponse
    {
        $validated = $this->validateQuote($request);

        $quote->update($this->payload($validated));

        return response()->json([
            'success' => true,
            'quote' => $quote->fresh(),
        ]);
    }

    /**
     * Delete a quote record.
     */
    public function destroy(Quote $quote): JsonResponse
    {
        $quote->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Validate quote request payload.
     *
     * @return array<string, mixed>
     */
    private function validateQuote(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:255'],
            'event_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_with:end_time'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'total_hours' => ['nullable', 'numeric', 'min:0'],
            'details' => ['nullable', 'string'],
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
        return [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'event_type' => $validated['event_type'] ?? null,
            'event_date' => $validated['event_date'] ?? null,
            'address' => $validated['address'] ?? null,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'total_hours' => $this->resolveTotalHours($validated),
            'details' => $validated['details'] ?? null,
        ];
    }

    /**
     * Resolve total hours from explicit input or start/end times.
     *
     * @param  array<string, mixed>  $validated
     */
    private function resolveTotalHours(array $validated): ?float
    {
        if (array_key_exists('total_hours', $validated) && $validated['total_hours'] !== null && $validated['total_hours'] !== '') {
            return (float) $validated['total_hours'];
        }

        if (! empty($validated['start_time']) && ! empty($validated['end_time'])) {
            $start = Carbon::createFromFormat('H:i', $validated['start_time']);
            $end = Carbon::createFromFormat('H:i', $validated['end_time']);
            $minutes = $end->diffInMinutes($start, false);

            if ($minutes > 0) {
                return round($minutes / 60, 2);
            }
        }

        return null;
    }
}
