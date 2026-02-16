<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Return all events in chronological order.
     */
    public function index(): JsonResponse
    {
        $events = Event::query()
            ->select([
                'id',
                'name',
                'type',
                'address',
                'date',
                'start_time',
                'end_time',
                'description',
                'image_url',
            ])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return response()->json($events)->header('Cache-Control', 'no-cache');
    }

    /**
     * Store a new event from the admin form.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:2048'],
        ]);

        if (($validated['type'] ?? null) === 'Private') {
            $validated['address'] = null;
        }

        Event::query()->create($validated);

        return response()->json(['success' => true]);
    }
}
