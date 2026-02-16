<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class QuoteController extends Controller
{
    /**
     * Store a quote request and send an email notification.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'event' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'details' => ['nullable', 'string'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ]);

        $totalHours = null;

        if (! empty($validated['start_time']) && ! empty($validated['end_time'])) {
            $start = Carbon::createFromFormat('H:i', $validated['start_time']);
            $end = Carbon::createFromFormat('H:i', $validated['end_time']);

            $minutes = $end->diffInMinutes($start, false);
            if ($minutes <= 0) {
                return response()->json([
                    'error' => 'End time must be after start time.',
                ], 422);
            }

            if ($minutes < 60) {
                return response()->json([
                    'error' => 'End time must be at least 1 hour after start time.',
                ], 422);
            }

            $totalHours = round($minutes / 60, 2);
        }

        try {
            $quote = Quote::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'event_type' => $validated['event'] ?? null,
                'event_date' => $validated['date'] ?? null,
                'address' => $validated['address'] ?? null,
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'total_hours' => $totalHours,
                'details' => $validated['details'] ?? null,
            ]);

            $this->sendQuoteEmail(
                name: $quote->name,
                email: $quote->email,
                eventType: $quote->event_type,
                date: optional($quote->event_date)->format('Y-m-d'),
                startTime: $quote->start_time,
                endTime: $quote->end_time,
                totalHours: $quote->total_hours,
                address: $quote->address,
                details: $quote->details,
            );

            return response()->json([
                'success' => true,
                'message' => 'Quote saved and email sent successfully!',
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'error' => 'Failed to save quote or send email.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Send Brevo notification email when credentials are configured.
     */
    private function sendQuoteEmail(
        string $name,
        string $email,
        ?string $eventType,
        ?string $date,
        ?string $startTime,
        ?string $endTime,
        ?float $totalHours,
        ?string $address,
        ?string $details,
    ): void {
        $apiKey = config('services.sprinkle.brevo_api_key');
        $fromEmail = config('services.sprinkle.brevo_from_email');
        $toEmail = config('services.sprinkle.brevo_to_email');

        if (! $apiKey || ! $fromEmail || ! $toEmail) {
            return;
        }

        $calculatorBaseUrl = rtrim(config('app.url', ''), '/').'/admin/calculator';

        $calculatorLink = sprintf(
            '%s?name=%s&email=%s&date=%s&start=%s&end=%s&hours=%s&type=%s',
            $calculatorBaseUrl,
            urlencode($name),
            urlencode($email),
            urlencode($date ?? ''),
            urlencode($startTime ?? ''),
            urlencode($endTime ?? ''),
            urlencode((string) ($totalHours ?? '')),
            urlencode($eventType ?? ''),
        );

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $apiKey,
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => 'Sprinkle Fairydust',
                'email' => $fromEmail,
            ],
            'to' => [[
                'email' => $toEmail,
                'name' => 'Melody',
            ]],
            'replyTo' => [
                'email' => $email,
                'name' => $name,
            ],
            'subject' => sprintf('New Quote Request: %s from %s', $eventType ?: 'Event', $name),
            'htmlContent' => sprintf(
                '<h2>New Quote Request ✨</h2>' .
                    '<p><strong>Name:</strong> %s</p>' .
                    '<p><strong>Email:</strong> %s</p>' .
                    '<p><strong>Event Type:</strong> %s</p>' .
                    '<p><strong>Event Date:</strong> %s</p>' .
                    '<p><strong>Start Time:</strong> %s</p>' .
                    '<p><strong>End Time:</strong> %s</p>' .
                    '<p><strong>Total Hours (rounded):</strong> %s</p>' .
                    '<p><strong>Address:</strong> %s</p>' .
                    '<p><strong>Additional Details:</strong></p><p>%s</p>' .
                    '<hr>' .
                    '<a href="%s" style="display:inline-block;margin-top:10px;color:#0066ff;">Open Quote Calculator</a>',
                e($name),
                e($email),
                e($eventType ?: '—'),
                e($date ?: '—'),
                e($startTime ?: '—'),
                e($endTime ?: '—'),
                e($totalHours !== null ? (string) $totalHours : '—'),
                e($address ?: '—'),
                nl2br(e($details ?: '—')),
                e($calculatorLink),
            ),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Brevo API request failed with status '.$response->status());
        }
    }
}
