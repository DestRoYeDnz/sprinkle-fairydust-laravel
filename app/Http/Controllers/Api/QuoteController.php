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
            'services_requested' => ['nullable', 'array', 'max:20'],
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

        Mail::to($toEmail, 'Sprinkle Fairydust Admin')->send(
            (new StyledHtmlMail(
                sprintf('New Quote Request: %s from %s', $quote->event_type ?: 'Event', $quote->name),
                $this->newQuoteRequestAdminEmailHtml($quote, $calculatorLink),
                $this->newQuoteRequestAdminEmailText($quote, $calculatorLink),
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

    private function newQuoteRequestAdminEmailHtml(Quote $quote, string $calculatorLink): string
    {
        $logoUrl = rtrim((string) config('app.url', ''), '/').'/images/logo.png';
        $eventDate = $quote->event_date?->format('l, j F Y') ?: 'To be confirmed';
        $termsAcceptedAt = $quote->terms_accepted_at?->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s');

        $snapshotRows = implode('', [
            $this->emailRow('Name', e($quote->name)),
            $this->emailRow('Email', e($quote->email)),
            $this->emailRow('Phone', e($quote->phone ?: '—')),
            $this->emailRow('Event Type', e($quote->event_type ?: '—')),
            $this->emailRow('Event Date', e($eventDate)),
            $this->emailRow('Start Time', e($this->formatTime($quote->start_time))),
            $this->emailRow('End Time', e($this->formatTime($quote->end_time))),
            $this->emailRow('Total Hours', e($quote->total_hours !== null ? number_format($quote->total_hours, 2) : '—')),
            $this->emailRow('Guest Count', e($quote->guest_count !== null ? (string) $quote->guest_count : '—')),
            $this->emailRow('Package', e($quote->package_name ?: '—')),
            $this->emailRow('Services', e($this->formatServices($quote->services_requested))),
            $this->emailRow('Travel Area', e($quote->travel_area ?: '—')),
            $this->emailRow('Venue Type', e($this->formatVenueType($quote->venue_type))),
            $this->emailRow('Heard About Us', e($quote->heard_about ?: '—')),
            $this->emailRow('Address', e($quote->address ?: '—')),
            $this->emailRow('Anonymous ID', e($quote->anonymous_id ?: '—')),
            $this->emailRow('Terms Accepted', e('Yes ('.$termsAcceptedAt.')')),
        ]);

        $notes = $quote->notes ? nl2br(e($quote->notes)) : '—';

        return '<!doctype html><html><head><meta charset="utf-8"></head><body style="margin:0;padding:0;background:#eaf7ff;font-family:Quicksand,Arial,sans-serif;color:#0f172a;">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:radial-gradient(circle at top,#bfdbfe 0%,#ccfbf1 45%,#f0f9ff 100%);padding:30px 12px;"><tr><td align="center">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:660px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #bae6fd;box-shadow:0 24px 44px rgba(14,116,144,0.16);">'
            .'<tr><td style="padding:12px 20px;background:#0c4a6e;text-align:center;">'
            .'<p style="margin:0;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#cffafe;font-weight:700;">Sprinkle Fairydust Face Painting</p>'
            .'</td></tr>'
            .'<tr><td style="padding:28px 26px 24px;background:linear-gradient(135deg,#dbeafe 0%,#ccfbf1 52%,#e0f2fe 100%);">'
            .'<img src="'.e($logoUrl).'" alt="Sprinkle Fairydust" style="width:128px;height:auto;display:block;margin:0 auto 12px auto;">'
            .'<h1 style="margin:0;text-align:center;font-size:34px;line-height:1.15;color:#0c4a6e;">New Quote Request</h1>'
            .'<p style="margin:10px auto 0 auto;max-width:520px;text-align:center;font-size:14px;line-height:1.6;color:#155e75;">A new quote request has been submitted from the website.</p>'
            .'</td></tr>'
            .'<tr><td style="padding:24px 26px 28px 26px;">'
            .'<div style="border:1px solid #bfdbfe;border-radius:16px;padding:15px 16px;background:#f8fbff;margin-bottom:14px;">'
            .'<p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#0369a1;font-weight:700;">Request Snapshot</p>'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">'
            .$snapshotRows
            .'<tr><td style="padding:8px 0;color:#475569;vertical-align:top;">Additional Details</td><td style="padding:8px 0;text-align:right;color:#0f172a;font-weight:700;">'.$notes.'</td></tr>'
            .'</table></div>'
            .'<div style="margin-top:16px;border-radius:14px;padding:14px 16px;background:#eff6ff;border:1px dashed #7dd3fc;">'
            .'<p style="margin:0;font-size:13px;line-height:1.7;color:#1e3a8a;">Open the calculator to review and prepare the client quote.</p>'
            .'</div>'
            .'<p style="margin:14px 0 0 0;"><a href="'.e($calculatorLink).'" style="display:inline-block;background:linear-gradient(135deg,#0ea5e9,#14b8a6);color:#ecfeff;text-decoration:none;font-weight:700;padding:11px 16px;border-radius:999px;">Open Quote Calculator</a></p>'
            .'<p style="margin:18px 0 0 0;font-size:14px;color:#0f172a;">With sparkles,<br><strong>Sprinkle Fairydust Face Painting</strong></p>'
            .'</td></tr>'
            .'</table>'
            .'<p style="margin:12px 0 0 0;text-align:center;font-size:12px;color:#64748b;">Creating magical moments, one painted smile at a time.</p>'
            .'</td></tr></table></body></html>';
    }

    private function newQuoteRequestAdminEmailText(Quote $quote, string $calculatorLink): string
    {
        $eventDate = $quote->event_date?->format('l, j F Y') ?: 'To be confirmed';

        return implode("\n", [
            'New Quote Request',
            '=================',
            '',
            'Name: '.($quote->name ?: '—'),
            'Email: '.($quote->email ?: '—'),
            'Phone: '.($quote->phone ?: '—'),
            'Event Type: '.($quote->event_type ?: '—'),
            'Event Date: '.$eventDate,
            'Start Time: '.$this->formatTime($quote->start_time),
            'End Time: '.$this->formatTime($quote->end_time),
            'Total Hours: '.($quote->total_hours !== null ? number_format($quote->total_hours, 2) : '—'),
            'Guest Count: '.($quote->guest_count !== null ? (string) $quote->guest_count : '—'),
            'Package: '.($quote->package_name ?: '—'),
            'Services: '.$this->formatServices($quote->services_requested),
            'Travel Area: '.($quote->travel_area ?: '—'),
            'Venue Type: '.$this->formatVenueType($quote->venue_type),
            'Heard About Us: '.($quote->heard_about ?: '—'),
            'Address: '.($quote->address ?: '—'),
            'Anonymous ID: '.($quote->anonymous_id ?: '—'),
            'Additional Details: '.($quote->notes ?: '—'),
            '',
            'Open Quote Calculator: '.$calculatorLink,
        ]);
    }

    /**
     * @param  array<int, string>|null  $services
     */
    private function formatServices(?array $services): string
    {
        if (! is_array($services) || $services === []) {
            return '—';
        }

        return implode(', ', $services);
    }

    private function emailRow(string $label, string $value): string
    {
        return '<tr>'
            .'<td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">'.e($label).'</td>'
            .'<td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.$value.'</td>'
            .'</tr>';
    }

    private function formatTime(?string $time): string
    {
        if (! $time) {
            return '—';
        }

        try {
            return Carbon::parse($time)->format('g:i A');
        } catch (\Throwable) {
            return $time;
        }
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
