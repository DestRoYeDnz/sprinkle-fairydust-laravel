<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\StyledHtmlMail;
use App\Models\Quote;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

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
            'phone' => ['nullable', 'string', 'max:48'],
            'guest_count' => ['nullable', 'integer', 'min:1', 'max:500'],
            'anonymous_id' => ['nullable', 'string', 'max:80'],
            'event' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'package_name' => ['nullable', 'string', 'max:120'],
            'services_requested' => ['nullable', 'array', 'max:8'],
            'services_requested.*' => ['string', 'max:80'],
            'travel_area' => ['nullable', 'string', 'max:255'],
            'venue_type' => ['nullable', Rule::in(['indoor', 'outdoor', 'mixed', 'unsure'])],
            'heard_about' => ['nullable', 'string', 'max:120'],
            'details' => ['nullable', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'terms_accepted' => ['required', 'accepted'],
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

        $notes = $this->normalizeOptionalString($validated['notes'] ?? $validated['details'] ?? null, 4000);

        try {
            $quote = Quote::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $this->sanitizePhone($validated['phone'] ?? null),
                'guest_count' => $this->normalizeGuestCount($validated['guest_count'] ?? null),
                'anonymous_id' => $this->sanitizeAnonymousId($validated['anonymous_id'] ?? null),
                'event_type' => $this->normalizeOptionalString($validated['event'] ?? null),
                'event_date' => $validated['date'] ?? null,
                'address' => $this->normalizeOptionalString($validated['address'] ?? null),
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'total_hours' => $totalHours,
                'package_name' => $this->normalizeOptionalString($validated['package_name'] ?? null, 120),
                'services_requested' => $this->sanitizeServicesRequested($validated['services_requested'] ?? null),
                'travel_area' => $this->normalizeOptionalString($validated['travel_area'] ?? null),
                'venue_type' => $this->normalizeOptionalString($validated['venue_type'] ?? null, 32),
                'heard_about' => $this->normalizeOptionalString($validated['heard_about'] ?? null, 120),
                'notes' => $notes,
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
            ]);

            $this->sendQuoteEmail($quote);

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
    private function sendQuoteEmail(Quote $quote): void
    {
        $toEmail = config('services.sprinkle.quote_notification_email')
            ?: 'brettj@dekode.co.nz';

        if (! $toEmail) {
            return;
        }

        $calculatorBaseUrl = rtrim(config('app.url', ''), '/').'/admin/calculator';

        $calculatorParams = http_build_query([
            'name' => $quote->name,
            'email' => $quote->email,
            'phone' => $quote->phone,
            'event' => $quote->event_type,
            'date' => optional($quote->event_date)->format('Y-m-d'),
            'start' => $quote->start_time,
            'end' => $quote->end_time,
            'hours' => $quote->total_hours,
            'guest_count' => $quote->guest_count,
            'package_name' => $quote->package_name,
            'services' => is_array($quote->services_requested) ? implode(',', $quote->services_requested) : null,
            'travel_area' => $quote->travel_area,
            'venue_type' => $quote->venue_type,
            'heard_about' => $quote->heard_about,
            'address' => $quote->address,
            'notes' => $quote->notes,
        ]);

        $calculatorLink = $calculatorParams !== ''
            ? "{$calculatorBaseUrl}?{$calculatorParams}"
            : $calculatorBaseUrl;

        $serviceListHtml = $this->serviceListHtml($quote->services_requested);
        $venueTypeLabel = $this->formatVenueType($quote->venue_type);

        Mail::to($toEmail, 'Sprinkle Fairydust Admin')->send(
            (new StyledHtmlMail(
                sprintf('New Quote Request: %s from %s', $quote->event_type ?: 'Event', $quote->name),
                sprintf(
                    '<h2>New Quote Request ✨</h2>'.
                        '<p><strong>Name:</strong> %s</p>'.
                        '<p><strong>Email:</strong> %s</p>'.
                        '<p><strong>Phone:</strong> %s</p>'.
                        '<p><strong>Event Type:</strong> %s</p>'.
                        '<p><strong>Event Date:</strong> %s</p>'.
                        '<p><strong>Start Time:</strong> %s</p>'.
                        '<p><strong>End Time:</strong> %s</p>'.
                        '<p><strong>Total Hours (rounded):</strong> %s</p>'.
                        '<p><strong>Guest Count:</strong> %s</p>'.
                        '<p><strong>Package:</strong> %s</p>'.
                        '<p><strong>Services Requested:</strong></p>%s'.
                        '<p><strong>Travel Area:</strong> %s</p>'.
                        '<p><strong>Venue Type:</strong> %s</p>'.
                        '<p><strong>How They Heard About Us:</strong> %s</p>'.
                        '<p><strong>Address:</strong> %s</p>'.
                        '<p><strong>Additional Details:</strong></p><p>%s</p>'.
                        '<p><strong>Terms Accepted:</strong> Yes (%s)</p>'.
                        '<hr>'.
                        '<a href="%s" style="display:inline-block;margin-top:10px;color:#0066ff;">Open Quote Calculator</a>',
                    e($quote->name),
                    e($quote->email),
                    e($quote->phone ?: '—'),
                    e($quote->event_type ?: '—'),
                    e(optional($quote->event_date)->format('Y-m-d') ?: '—'),
                    e($quote->start_time ?: '—'),
                    e($quote->end_time ?: '—'),
                    e($quote->total_hours !== null ? (string) $quote->total_hours : '—'),
                    e($quote->guest_count !== null ? (string) $quote->guest_count : '—'),
                    e($quote->package_name ?: '—'),
                    $serviceListHtml,
                    e($quote->travel_area ?: '—'),
                    e($venueTypeLabel),
                    e($quote->heard_about ?: '—'),
                    e($quote->address ?: '—'),
                    nl2br(e($quote->notes ?: '—')),
                    e(optional($quote->terms_accepted_at)->toDateTimeString() ?: now()->toDateTimeString()),
                    e($calculatorLink),
                ),
            ))->replyTo($quote->email, $quote->name)
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

    private function sanitizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $sanitized = preg_replace('/[^0-9+\-\s().]/', '', trim($phone)) ?? '';

        if ($sanitized === '') {
            return null;
        }

        return substr($sanitized, 0, 48);
    }

    private function normalizeGuestCount(mixed $guestCount): ?int
    {
        if ($guestCount === null || $guestCount === '') {
            return null;
        }

        $normalized = (int) $guestCount;

        return $normalized > 0 ? $normalized : null;
    }

    private function normalizeOptionalString(mixed $value, int $maxLength = 255): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        return mb_substr($normalized, 0, $maxLength);
    }

    /**
     * @param  array<int, string>|null  $services
     * @return array<int, string>|null
     */
    private function sanitizeServicesRequested(?array $services): ?array
    {
        if (! is_array($services) || $services === []) {
            return null;
        }

        $normalized = collect($services)
            ->map(fn (mixed $item): string => trim((string) $item))
            ->filter(fn (string $item): bool => $item !== '')
            ->map(fn (string $item): string => mb_substr($item, 0, 80))
            ->unique()
            ->values()
            ->all();

        return $normalized === [] ? null : $normalized;
    }

    /**
     * @param  array<int, string>|null  $services
     */
    private function serviceListHtml(?array $services): string
    {
        if (! is_array($services) || $services === []) {
            return '<p>—</p>';
        }

        $items = collect($services)
            ->map(fn (string $service): string => '<li>'.e($service).'</li>')
            ->implode('');

        return "<ul>{$items}</ul>";
    }

    private function formatVenueType(?string $venueType): string
    {
        if (! $venueType) {
            return '—';
        }

        return match ($venueType) {
            'indoor' => 'Indoor',
            'outdoor' => 'Outdoor',
            'mixed' => 'Indoor + Outdoor',
            'unsure' => 'Not sure yet',
            default => ucfirst($venueType),
        };
    }
}
