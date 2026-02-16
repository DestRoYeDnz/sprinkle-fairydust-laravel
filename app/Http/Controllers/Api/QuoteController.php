<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\StyledHtmlMail;
use App\Models\Quote;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
            'anonymous_id' => ['nullable', 'string', 'max:80'],
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
                'anonymous_id' => $this->sanitizeAnonymousId($validated['anonymous_id'] ?? null),
                'event_type' => $validated['event'] ?? null,
                'event_date' => $validated['date'] ?? null,
                'address' => $validated['address'] ?? null,
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'total_hours' => $totalHours,
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
                details: $validated['details'] ?? null,
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
     * Send quote notification email when a recipient is configured.
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
        $toEmail = config('services.sprinkle.quote_notification_email')
            ?: 'brettj@dekode.co.nz';

        if (! $toEmail) {
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

        Mail::to($toEmail, 'Sprinkle Fairydust Admin')->send(
            (new StyledHtmlMail(
                sprintf('New Quote Request: %s from %s', $eventType ?: 'Event', $name),
                sprintf(
                    '<h2>New Quote Request ✨</h2>'.
                        '<p><strong>Name:</strong> %s</p>'.
                        '<p><strong>Email:</strong> %s</p>'.
                        '<p><strong>Event Type:</strong> %s</p>'.
                        '<p><strong>Event Date:</strong> %s</p>'.
                        '<p><strong>Start Time:</strong> %s</p>'.
                        '<p><strong>End Time:</strong> %s</p>'.
                        '<p><strong>Total Hours (rounded):</strong> %s</p>'.
                        '<p><strong>Address:</strong> %s</p>'.
                        '<p><strong>Additional Details:</strong></p><p>%s</p>'.
                        '<hr>'.
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
            ))->replyTo($email, $name)
        );
    }

    private function sanitizeAnonymousId(?string $anonymousId): ?string
    {
        if ($anonymousId === null) {
            return null;
        }

        $sanitized = preg_replace('/[^A-Za-z0-9\-_]/', '', $anonymousId) ?? '';

        if ($sanitized === '') {
            return null;
        }

        return substr($sanitized, 0, 80);
    }
}
