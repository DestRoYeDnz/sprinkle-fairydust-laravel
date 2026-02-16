<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\StyledHtmlMail;
use App\Models\Quote;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

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
                'anonymous_id',
                'event_type',
                'event_date',
                'address',
                'start_time',
                'end_time',
                'total_hours',
                'calc_payment_type',
                'calc_base_amount',
                'calc_setup_amount',
                'calc_travel_amount',
                'calc_subtotal',
                'calc_gst_amount',
                'calc_total_amount',
                'email_send_status',
                'email_send_attempted_at',
                'email_send_response',
                'client_confirmed_at',
                'email_opened_at',
                'email_last_opened_at',
                'email_open_count',
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
     * Send a styled quote email to the quote contact.
     */
    public function sendEmail(Quote $quote): JsonResponse
    {
        if (! $quote->email) {
            return response()->json([
                'message' => 'Quote email address is missing.',
            ], 422);
        }

        $fromEmail = config('mail.from.address');
        $adminCopyEmail = config('services.sprinkle.quote_admin_copy_email');

        if (! $fromEmail) {
            return response()->json([
                'message' => 'Email is not configured. Set MAIL_FROM_ADDRESS.',
            ], 422);
        }

        $subject = sprintf(
            'Your Sprinkle Fairydust Quote%s',
            $quote->event_type ? ' - '.$quote->event_type : '',
        );
        $attemptedAt = now();
        $actionUrls = $this->quoteEmailActionUrls($quote);

        try {
            $pendingMail = Mail::to($quote->email, $quote->name ?: 'Valued Client');

            if ($adminCopyEmail) {
                $pendingMail->bcc($adminCopyEmail, 'Sprinkle Fairydust Admin');
            }

            $sentMessage = $pendingMail->send(new StyledHtmlMail(
                $subject,
                $this->quoteEmailHtml($quote, $actionUrls),
                $this->quoteEmailText($quote, $actionUrls),
            ));

            $this->persistEmailSendResult(
                quote: $quote,
                status: 'sent',
                attemptedAt: $attemptedAt,
                response: $this->successfulEmailResponsePayload($sentMessage, $quote, $adminCopyEmail),
            );

            return response()->json([
                'success' => true,
                'message' => 'Quote email sent successfully.',
            ]);
        } catch (\Throwable $exception) {
            $this->persistEmailSendResult(
                quote: $quote,
                status: 'failed',
                attemptedAt: $attemptedAt,
                response: $this->failedEmailResponsePayload($exception, $quote, $adminCopyEmail),
            );

            return response()->json([
                'message' => 'Failed to send quote email.',
            ], 500);
        }
    }

    /**
     * Confirm quote from a secure signed email link.
     */
    public function confirmFromEmail(Quote $quote): \Illuminate\Http\Response
    {
        $alreadyConfirmed = $quote->client_confirmed_at !== null;

        if (! $alreadyConfirmed) {
            $quote->forceFill([
                'client_confirmed_at' => now(),
            ])->save();
        }

        $eventType = $quote->event_type ?: 'your event';
        $logoUrl = rtrim((string) config('app.url', ''), '/').'/images/logo.png';
        $headline = $alreadyConfirmed
            ? 'Already Confirmed'
            : 'Quote Confirmed';
        $message = $alreadyConfirmed
            ? 'This quote has already been confirmed. Thank you for choosing Sprinkle Fairydust.'
            : 'Your quote for '.e($eventType).' is now confirmed. Thank you for booking with Sprinkle Fairydust.';

        $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>'
            .'<body style="margin:0;padding:0;background:linear-gradient(180deg,#e0f2fe 0%,#ecfeff 55%,#f0f9ff 100%);font-family:Quicksand,Arial,sans-serif;color:#0f172a;">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 14px;"><tr><td align="center">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;border-radius:24px;overflow:hidden;border:1px solid #bae6fd;background:#ffffff;box-shadow:0 24px 44px rgba(14,116,144,0.18);">'
            .'<tr><td style="padding:28px 24px 24px;background:radial-gradient(circle at top right,#99f6e4 0%,#bae6fd 46%,#dbeafe 100%);text-align:center;">'
            .'<p style="margin:0 0 8px 0;font-size:11px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:#0f766e;">Sprinkle Fairydust Face Painting</p>'
            .'<img src="'.e($logoUrl).'" alt="Sprinkle Fairydust" style="display:block;width:120px;max-width:100%;height:auto;margin:0 auto 12px auto;">'
            .'<h1 style="margin:0;font-size:32px;line-height:1.2;color:#0c4a6e;">'.$headline.'</h1>'
            .'<p style="margin:8px 0 0 0;font-size:14px;color:#155e75;">Quote Confirmation</p>'
            .'</td></tr>'
            .'<tr><td style="padding:24px 24px 26px 24px;">'
            .'<div style="border:1px solid #bfdbfe;border-radius:16px;padding:16px 16px 14px 16px;background:linear-gradient(180deg,#f8fbff 0%,#f0fdfa 100%);">'
            .'<p style="margin:0 0 8px 0;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#0369a1;">Status</p>'
            .'<p style="margin:0;font-size:16px;line-height:1.6;color:#0f172a;">'.$message.'</p>'
            .'</div>'
            .'<p style="margin:16px 0 0 0;font-size:14px;line-height:1.7;color:#334155;">We are so excited to bring colour, fun, and sparkle to your celebration.</p>'
            .'<p style="margin:18px 0 0 0;font-size:14px;color:#0f172a;">With sparkles,<br><strong>Sprinkle Fairydust Face Painting</strong></p>'
            .'</td></tr>'
            .'</table>'
            .'<p style="margin:14px 0 0 0;text-align:center;font-size:12px;color:#64748b;">Creating magical moments, one painted smile at a time.</p>'
            .'</td></tr></table></body></html>';

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Track quote email opens from a secure signed tracking pixel.
     */
    public function trackEmailOpen(Quote $quote): \Illuminate\Http\Response
    {
        $openedAt = now();

        $quote->forceFill([
            'email_opened_at' => $quote->email_opened_at ?: $openedAt,
            'email_last_opened_at' => $openedAt,
            'email_open_count' => ($quote->email_open_count ?? 0) + 1,
        ])->save();

        $pixel = base64_decode('R0lGODlhAQABAPAAAP///wAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==');

        return response($pixel, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
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
            'anonymous_id' => ['nullable', 'string', 'max:80'],
            'event_type' => ['nullable', 'string', 'max:255'],
            'event_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_with:end_time'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'total_hours' => ['nullable', 'numeric', 'min:0'],
            'calc_payment_type' => ['nullable', Rule::in(['hourly', 'perface'])],
            'calc_base_amount' => ['nullable', 'numeric', 'min:0'],
            'calc_setup_amount' => ['nullable', 'numeric', 'min:0'],
            'calc_travel_amount' => ['nullable', 'numeric', 'min:0'],
            'calc_subtotal' => ['nullable', 'numeric', 'min:0'],
            'calc_gst_amount' => ['nullable', 'numeric', 'min:0'],
            'calc_total_amount' => ['nullable', 'numeric', 'min:0'],
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
        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'event_type' => $validated['event_type'] ?? null,
            'event_date' => $validated['event_date'] ?? null,
            'address' => $validated['address'] ?? null,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'total_hours' => $this->resolveTotalHours($validated),
            'calc_payment_type' => $validated['calc_payment_type'] ?? null,
            'calc_base_amount' => $this->nullableFloat($validated, 'calc_base_amount'),
            'calc_setup_amount' => $this->nullableFloat($validated, 'calc_setup_amount'),
            'calc_travel_amount' => $this->nullableFloat($validated, 'calc_travel_amount'),
            'calc_subtotal' => $this->nullableFloat($validated, 'calc_subtotal'),
            'calc_gst_amount' => $this->nullableFloat($validated, 'calc_gst_amount'),
            'calc_total_amount' => $this->nullableFloat($validated, 'calc_total_amount'),
        ];

        if (array_key_exists('anonymous_id', $validated)) {
            $payload['anonymous_id'] = $this->sanitizeAnonymousId($validated['anonymous_id']);
        }

        return $payload;
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

    /**
     * Coerce a numeric validated field to nullable float.
     *
     * @param  array<string, mixed>  $validated
     */
    private function nullableFloat(array $validated, string $key): ?float
    {
        if (! array_key_exists($key, $validated) || $validated[$key] === null || $validated[$key] === '') {
            return null;
        }

        return (float) $validated[$key];
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

    /**
     * Build branded HTML quote email.
     */
    private function quoteEmailHtml(Quote $quote, array $actionUrls): string
    {
        $logoUrl = rtrim((string) config('app.url', ''), '/').'/images/logo.png';
        $contactEmail = (string) config('mail.from.address', '');
        $eventType = $quote->event_type ?: 'Event';
        $eventDate = $quote->event_date?->format('l, j F Y') ?: 'Date to be confirmed';
        $startTime = $this->formatTime($quote->start_time);
        $endTime = $this->formatTime($quote->end_time);
        $hours = $quote->total_hours !== null ? number_format($quote->total_hours, 2).' hours' : 'To be confirmed';
        $confirmUrl = $actionUrls['confirm'] ?? null;
        $openTrackingUrl = $actionUrls['open'] ?? null;

        $calculationRows = implode('', array_filter([
            $this->quoteEmailRow('Payment Type', $this->paymentTypeLabel($quote->calc_payment_type)),
            $this->quoteEmailRow('Base', $this->formatCurrency($quote->calc_base_amount)),
            $this->quoteEmailRow('Setup', $this->formatCurrency($quote->calc_setup_amount)),
            $this->quoteEmailRow('Travel', $this->formatCurrency($quote->calc_travel_amount)),
            $this->quoteEmailRow('Subtotal', $this->formatCurrency($quote->calc_subtotal)),
            $this->quoteEmailRow('GST', $this->formatCurrency($quote->calc_gst_amount)),
            $this->quoteEmailRow('Total', '<strong>'.$this->formatCurrency($quote->calc_total_amount).'</strong>'),
        ]));

        return '<!doctype html><html><head><meta charset="utf-8"></head><body style="margin:0;padding:0;background:#eaf7ff;font-family:Quicksand,Arial,sans-serif;color:#0f172a;">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:radial-gradient(circle at top,#bfdbfe 0%,#ccfbf1 45%,#f0f9ff 100%);padding:30px 12px;"><tr><td align="center">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:660px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #bae6fd;box-shadow:0 24px 44px rgba(14,116,144,0.16);">'
            .'<tr><td style="padding:12px 20px;background:#0c4a6e;text-align:center;">'
            .'<p style="margin:0;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#cffafe;font-weight:700;">Sprinkle Fairydust Face Painting</p>'
            .'</td></tr>'
            .'<tr><td style="padding:28px 26px 24px;background:linear-gradient(135deg,#dbeafe 0%,#ccfbf1 52%,#e0f2fe 100%);">'
            .'<img src="'.e($logoUrl).'" alt="Sprinkle Fairydust" style="width:128px;height:auto;display:block;margin:0 auto 12px auto;">'
            .'<h1 style="margin:0;text-align:center;font-size:34px;line-height:1.15;color:#0c4a6e;">Your Sprinkle Fairydust Quote</h1>'
            .'<p style="margin:10px auto 0 auto;max-width:520px;text-align:center;font-size:14px;line-height:1.6;color:#155e75;">Thank you for choosing Sprinkle Fairydust. Here is your magical quote, crafted for smiles, sparkle, and a colourful event day.</p>'
            .'</td></tr>'
            .'<tr><td style="padding:24px 26px 28px 26px;">'
            .'<p style="margin:0 0 12px 0;font-size:16px;color:#0f172a;">Hi '.e($quote->name ?: 'there').',</p>'
            .'<p style="margin:0 0 15px 0;font-size:14px;line-height:1.7;color:#334155;">We cannot wait to paint at your event. Your quote details are below so you can confirm with confidence.</p>'
            .'<div style="border:1px solid #bfdbfe;border-radius:16px;padding:15px 16px;background:#f8fbff;margin-bottom:14px;">'
            .'<p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#0369a1;font-weight:700;">Event Snapshot</p>'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">'
            .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Event Type</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($eventType).'</td></tr>'
            .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Date</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($eventDate).'</td></tr>'
            .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Start Time</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($startTime).'</td></tr>'
            .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">End Time</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($endTime).'</td></tr>'
            .'<tr><td style="padding:8px 0;color:#475569;">Duration</td><td style="padding:8px 0;text-align:right;color:#0f172a;font-weight:700;">'.e($hours).'</td></tr>'
            .($quote->address ? '<tr><td style="padding:8px 0;border-top:1px solid #e2e8f0;color:#475569;">Location</td><td style="padding:8px 0;border-top:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($quote->address).'</td></tr>' : '')
            .'</table></div>'
            .'<div style="border:1px solid #99f6e4;border-radius:16px;padding:15px 16px;background:linear-gradient(180deg,#f8fffe 0%,#f0fdfa 100%);">'
            .'<p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#0f766e;font-weight:700;">Quote Breakdown</p>'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">'
            .$calculationRows
            .'</table>'
            .'<p style="margin:12px 0 0 0;font-size:13px;line-height:1.6;color:#115e59;">Includes setup and travel as listed above, with friendly service and professional face painting on the day.</p>'
            .'</div>'
            .'<div style="margin-top:16px;border-radius:14px;padding:14px 16px;background:#eff6ff;border:1px dashed #7dd3fc;">'
            .'<p style="margin:0;font-size:13px;line-height:1.7;color:#1e3a8a;">Ready to book? Confirm your quote below and we will lock in your date.</p>'
            .'</div>'
            .($confirmUrl
                ? '<p style="margin:14px 0 0 0;"><a href="'.e($confirmUrl).'" style="display:inline-block;background:linear-gradient(135deg,#0ea5e9,#14b8a6);color:#ecfeff;text-decoration:none;font-weight:700;padding:11px 16px;border-radius:999px;">Confirm Your Quote</a></p>'
                : '')
            .($contactEmail !== '' ? '<p style="margin:10px 0 0 0;font-size:12px;color:#64748b;">Need changes first? Reply to this email or contact us at '.e($contactEmail).'.</p>' : '')
            .'<p style="margin:18px 0 0 0;font-size:14px;color:#0f172a;">With sparkles,<br><strong>Sprinkle Fairydust Face Painting</strong></p>'
            .'</td></tr>'
            .'</table>'
            .'<p style="margin:12px 0 0 0;text-align:center;font-size:12px;color:#64748b;">Creating magical moments, one painted smile at a time.</p>'
            .($openTrackingUrl ? '<img src="'.e($openTrackingUrl).'" alt="" width="1" height="1" style="display:block;width:1px;height:1px;opacity:0;border:0;">' : '')
            .'</td></tr></table></body></html>';
    }

    /**
     * Build plain text fallback quote email.
     */
    private function quoteEmailText(Quote $quote, array $actionUrls): string
    {
        $eventType = $quote->event_type ?: 'Event';
        $eventDate = $quote->event_date?->format('l, j F Y') ?: 'Date to be confirmed';
        $startTime = $this->formatTime($quote->start_time);
        $endTime = $this->formatTime($quote->end_time);
        $hours = $quote->total_hours !== null ? number_format($quote->total_hours, 2).' hours' : 'To be confirmed';
        $contactEmail = (string) config('mail.from.address', '');
        $confirmUrl = $actionUrls['confirm'] ?? '';

        $lines = [
            'Sprinkle Fairydust Quote',
            '========================',
            '',
            'Hi '.($quote->name ?: 'there').',',
            '',
            'Thank you for inviting Sprinkle Fairydust to be part of your celebration.',
            'Here is your quote summary:',
            '',
            'Event Snapshot',
            '--------------',
            'Event Type: '.$eventType,
            'Date: '.$eventDate,
            'Start: '.$startTime,
            'End: '.$endTime,
            'Duration: '.$hours,
            'Location: '.($quote->address ?: 'To be confirmed'),
            '',
            'Quote Breakdown',
            '---------------',
            'Payment Type: '.$this->paymentTypeLabel($quote->calc_payment_type),
            'Base: '.$this->formatCurrency($quote->calc_base_amount),
            'Setup: '.$this->formatCurrency($quote->calc_setup_amount),
            'Travel: '.$this->formatCurrency($quote->calc_travel_amount),
            'Subtotal: '.$this->formatCurrency($quote->calc_subtotal),
            'GST: '.$this->formatCurrency($quote->calc_gst_amount),
            'Total: '.$this->formatCurrency($quote->calc_total_amount),
            '',
            'Ready to confirm?',
        ];

        if ($confirmUrl !== '') {
            $lines[] = 'Confirm your quote: '.$confirmUrl;
        }

        if ($contactEmail !== '') {
            $lines[] = 'Questions? Contact us at: '.$contactEmail;
        }

        $lines[] = '';
        $lines[] = 'With sparkles,';
        $lines[] = 'Sprinkle Fairydust Face Painting';

        return implode("\n", $lines);
    }

    /**
     * Build signed action URLs used in quote emails.
     *
     * @return array{confirm: string, open: string}
     */
    private function quoteEmailActionUrls(Quote $quote): array
    {
        $expiresAt = now()->addDays((int) config('services.sprinkle.quote_link_expiry_days', 45));

        return [
            'confirm' => URL::temporarySignedRoute('quotes.confirm', $expiresAt, ['quote' => $quote->id]),
            'open' => URL::temporarySignedRoute('quotes.open', $expiresAt, ['quote' => $quote->id]),
        ];
    }

    private function quoteEmailRow(string $label, string $value): string
    {
        return '<tr>'
            .'<td style="padding:7px 0;border-bottom:1px solid #e2e8f0;color:#475569;">'.e($label).'</td>'
            .'<td style="padding:7px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;">'.$value.'</td>'
            .'</tr>';
    }

    private function paymentTypeLabel(?string $paymentType): string
    {
        if ($paymentType === 'hourly') {
            return 'Organizer-Paid (Hourly)';
        }

        if ($paymentType === 'perface') {
            return 'Pay Per Face';
        }

        return 'To be confirmed';
    }

    private function formatCurrency(?float $amount): string
    {
        if ($amount === null) {
            return 'To be confirmed';
        }

        return '$'.number_format($amount, 2);
    }

    private function formatTime(?string $time): string
    {
        if (! $time) {
            return 'To be confirmed';
        }

        try {
            return Carbon::parse($time)->format('g:i A');
        } catch (\Throwable) {
            return $time;
        }
    }

    /**
     * Persist the latest quote email send metadata.
     *
     * @param  array<string, mixed>  $response
     */
    private function persistEmailSendResult(Quote $quote, string $status, \DateTimeInterface $attemptedAt, array $response): void
    {
        try {
            $quote->forceFill([
                'email_send_status' => $status,
                'email_send_attempted_at' => $attemptedAt,
                'email_send_response' => $response,
            ])->save();
        } catch (\Throwable) {
            // Do not fail the request if metadata persistence fails.
        }
    }

    /**
     * Build SMTP success payload for quote email sends.
     *
     * @return array<string, mixed>
     */
    private function successfulEmailResponsePayload(?SentMessage $sentMessage, Quote $quote, ?string $adminCopyEmail): array
    {
        return [
            'ok' => true,
            'mailer' => config('mail.default'),
            'to' => $quote->email,
            'bcc' => $adminCopyEmail,
            'message_id' => $sentMessage?->getMessageId(),
        ];
    }

    /**
     * Build SMTP failure payload for quote email sends.
     *
     * @return array<string, mixed>
     */
    private function failedEmailResponsePayload(\Throwable $exception, Quote $quote, ?string $adminCopyEmail): array
    {
        return [
            'ok' => false,
            'mailer' => config('mail.default'),
            'to' => $quote->email,
            'bcc' => $adminCopyEmail,
            'error' => $exception->getMessage(),
            'exception' => $exception::class,
        ];
    }
}
