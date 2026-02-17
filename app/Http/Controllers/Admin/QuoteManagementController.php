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
                'phone',
                'guest_count',
                'package_name',
                'services_requested',
                'travel_area',
                'venue_type',
                'heard_about',
                'notes',
                'terms_accepted',
                'terms_accepted_at',
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
                'artist_declined_at',
                'artist_decline_reason',
                'client_suggested_time_at',
                'client_suggested_event_date',
                'client_suggested_start_time',
                'client_suggested_end_time',
                'client_suggested_time_notes',
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

            $this->sendQuoteConfirmedNotification($quote);
            $this->sendClientQuoteConfirmedEmail($quote);
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
     * Show a signed page that lets the client suggest a different event time.
     */
    public function showSuggestedTimeForm(Quote $quote): \Illuminate\Http\Response
    {
        $actionUrls = $this->quoteSuggestedTimeActionUrls($quote);
        $values = [
            'event_date' => $quote->client_suggested_event_date?->format('Y-m-d') ?: $quote->event_date?->format('Y-m-d') ?: '',
            'start_time' => $this->formatTimeInput($quote->client_suggested_start_time ?: $quote->start_time),
            'end_time' => $this->formatTimeInput($quote->client_suggested_end_time ?: $quote->end_time),
            'notes' => $quote->client_suggested_time_notes ?: '',
        ];

        return response($this->suggestedTimeFormHtml(
            quote: $quote,
            submitUrl: $actionUrls['submit'],
            values: $values,
        ))
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Persist a client suggested time from a signed link and notify both parties.
     */
    public function submitSuggestedTime(Request $request, Quote $quote): \Illuminate\Http\Response
    {
        if ($quote->client_confirmed_at !== null) {
            return response($this->suggestedTimeSubmittedHtml(
                title: 'Quote Already Confirmed',
                message: 'This booking is already confirmed, so a new time request is no longer needed.',
            ), 422)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Cache-Control', 'no-store, private');
        }

        $validator = validator(
            $request->all(),
            [
                'event_date' => ['required', 'date'],
                'start_time' => ['required', 'date_format:H:i'],
                'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ],
            [
                'event_date.required' => 'Please choose a new preferred date.',
                'start_time.required' => 'Please choose a preferred start time.',
                'end_time.required' => 'Please choose a preferred end time.',
                'end_time.after' => 'End time must be after start time.',
            ],
        );

        if ($validator->fails()) {
            $actionUrls = $this->quoteSuggestedTimeActionUrls($quote);

            return response($this->suggestedTimeFormHtml(
                quote: $quote,
                submitUrl: $actionUrls['submit'],
                values: [
                    'event_date' => (string) $request->input('event_date', ''),
                    'start_time' => (string) $request->input('start_time', ''),
                    'end_time' => (string) $request->input('end_time', ''),
                    'notes' => (string) $request->input('notes', ''),
                ],
                errorMessage: (string) $validator->errors()->first(),
            ), 422)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Cache-Control', 'no-store, private');
        }

        $validated = $validator->validated();

        $quote->forceFill([
            'client_suggested_time_at' => now(),
            'client_suggested_event_date' => $validated['event_date'],
            'client_suggested_start_time' => $validated['start_time'],
            'client_suggested_end_time' => $validated['end_time'],
            'client_suggested_time_notes' => $this->nullableTrimmedString($validated, 'notes', 2000),
        ])->save();

        $this->sendSuggestedTimeNotificationToAdmin($quote);
        $this->sendSuggestedTimeReceiptToClient($quote);

        return response($this->suggestedTimeSubmittedHtml(
            title: 'Thanks! We Received Your New Time',
            message: 'Your new preferred time has been sent to Sprinkle Fairydust. We will be in touch shortly.',
        ))
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Mark a quote as declined when the requested time is unavailable.
     */
    public function decline(Request $request, Quote $quote): JsonResponse
    {
        if ($quote->client_confirmed_at !== null) {
            return response()->json([
                'message' => 'Confirmed quotes cannot be declined.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $reason = trim((string) ($validated['reason'] ?? ''));

        if ($reason === '') {
            $reason = 'Requested time does not suit our availability. Please reply with another preferred time.';
        }

        $quote->forceFill([
            'artist_declined_at' => now(),
            'artist_decline_reason' => $reason,
            'client_suggested_time_at' => null,
            'client_suggested_event_date' => null,
            'client_suggested_start_time' => null,
            'client_suggested_end_time' => null,
            'client_suggested_time_notes' => null,
        ])->save();

        $this->sendClientQuoteDeclinedEmail($quote);

        return response()->json([
            'success' => true,
            'message' => 'Quote marked as declined.',
            'quote' => $quote->fresh(),
        ]);
    }

    /**
     * Send an admin notification when a client confirms a quote.
     */
    private function sendQuoteConfirmedNotification(Quote $quote): void
    {
        $toEmail = config('services.sprinkle.quote_confirmed_notification_email')
            ?: config('services.sprinkle.quote_notification_email')
            ?: 'brettj@dekode.co.nz';

        if (! is_string($toEmail) || trim($toEmail) === '') {
            return;
        }

        $eventType = $quote->event_type ?: 'Event';

        try {
            Mail::to($toEmail, 'Sprinkle Fairydust Admin')->send(new StyledHtmlMail(
                sprintf('Quote Confirmed: %s (%s)', $quote->name ?: 'Client', $eventType),
                $this->quoteConfirmedAdminEmailHtml($quote),
                $this->quoteConfirmedAdminEmailText($quote),
            ));
        } catch (\Throwable) {
            // Do not fail quote confirmation if admin notification cannot be sent.
        }
    }

    /**
     * Send a confirmation receipt to the customer who confirmed the quote.
     */
    private function sendClientQuoteConfirmedEmail(Quote $quote): void
    {
        if (! $quote->email) {
            return;
        }

        try {
            Mail::to($quote->email, $quote->name ?: 'Valued Client')->send(new StyledHtmlMail(
                sprintf('Your Quote is Confirmed%s', $quote->event_type ? ' - '.$quote->event_type : ''),
                $this->quoteConfirmedClientEmailHtml($quote),
                $this->quoteConfirmedClientEmailText($quote),
            ));
        } catch (\Throwable) {
            // Do not fail confirmation flow if customer receipt email cannot be sent.
        }
    }

    /**
     * Send an availability update when a quote is declined by the artist.
     */
    private function sendClientQuoteDeclinedEmail(Quote $quote): void
    {
        if (! $quote->email) {
            return;
        }

        $logoUrl = rtrim((string) config('app.url', ''), '/').'/images/logo.png';
        $eventType = $quote->event_type ?: 'your event';
        $eventDate = $quote->event_date?->format('l, j F Y') ?: 'To be confirmed';
        $startTime = $this->formatTime($quote->start_time);
        $endTime = $this->formatTime($quote->end_time);
        $reason = $quote->artist_decline_reason ?: 'Requested time does not suit our availability.';
        $contactEmail = (string) config('mail.from.address', '');
        $actionUrls = $this->quoteSuggestedTimeActionUrls($quote);
        $suggestTimeUrl = $actionUrls['suggest'] ?? null;

        try {
            $htmlContent = '<!doctype html><html><head><meta charset="utf-8"></head><body style="margin:0;padding:0;background:#eaf7ff;font-family:Quicksand,Arial,sans-serif;color:#0f172a;">'
                .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:radial-gradient(circle at top,#bfdbfe 0%,#ccfbf1 45%,#f0f9ff 100%);padding:30px 12px;"><tr><td align="center">'
                .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:660px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #bae6fd;box-shadow:0 24px 44px rgba(14,116,144,0.16);">'
                .'<tr><td style="padding:12px 20px;background:#0c4a6e;text-align:center;">'
                .'<p style="margin:0;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#cffafe;font-weight:700;">Sprinkle Fairydust Face Painting</p>'
                .'</td></tr>'
                .'<tr><td style="padding:28px 26px 24px;background:linear-gradient(135deg,#dbeafe 0%,#ccfbf1 52%,#e0f2fe 100%);">'
                .'<img src="'.e($logoUrl).'" alt="Sprinkle Fairydust" style="width:128px;height:auto;display:block;margin:0 auto 12px auto;">'
                .'<h1 style="margin:0;text-align:center;font-size:34px;line-height:1.15;color:#0c4a6e;">Quote Time Update</h1>'
                .'<p style="margin:10px auto 0 auto;max-width:520px;text-align:center;font-size:14px;line-height:1.6;color:#155e75;">We could not fit your original requested time, but we would love to find another option that works.</p>'
                .'</td></tr>'
                .'<tr><td style="padding:24px 26px 28px 26px;">'
                .'<p style="margin:0 0 12px 0;font-size:16px;color:#0f172a;">Hi '.e($quote->name ?: 'there').',</p>'
                .'<p style="margin:0 0 15px 0;font-size:14px;line-height:1.7;color:#334155;">Thank you for your quote request. We are unavailable at the requested time, so this quote is currently marked as unavailable for that time slot.</p>'
                .'<div style="border:1px solid #bfdbfe;border-radius:16px;padding:15px 16px;background:#f8fbff;margin-bottom:14px;">'
                .'<p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#0369a1;font-weight:700;">Requested Time</p>'
                .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">'
                .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Event</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($eventType).'</td></tr>'
                .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Date</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($eventDate).'</td></tr>'
                .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Start</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($startTime).'</td></tr>'
                .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">End</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($endTime).'</td></tr>'
                .'<tr><td style="padding:8px 0;color:#475569;">Reason</td><td style="padding:8px 0;text-align:right;color:#0f172a;font-weight:700;">'.e($reason).'</td></tr>'
                .'</table></div>'
                .'<div style="margin-top:16px;border-radius:14px;padding:14px 16px;background:#eff6ff;border:1px dashed #7dd3fc;">'
                .'<p style="margin:0;font-size:13px;line-height:1.7;color:#1e3a8a;">If another time works for you, use the button below to suggest a new time and we will review it promptly.</p>'
                .'</div>'
                .($suggestTimeUrl
                    ? '<p style="margin:14px 0 0 0;"><a href="'.e($suggestTimeUrl).'" style="display:inline-block;background:linear-gradient(135deg,#0ea5e9,#14b8a6);color:#ecfeff;text-decoration:none;font-weight:700;padding:11px 16px;border-radius:999px;">Suggest a Different Time</a></p>'
                    : '')
                .($contactEmail !== ''
                    ? '<p style="margin:10px 0 0 0;font-size:12px;color:#64748b;">Need help? Reply to this email or contact us at '.e($contactEmail).'.</p>'
                    : '<p style="margin:10px 0 0 0;font-size:12px;color:#64748b;">Need help? Reply to this email and we can work through options.</p>')
                .'<p style="margin:18px 0 0 0;font-size:14px;color:#0f172a;">With sparkles,<br><strong>Sprinkle Fairydust Face Painting</strong></p>'
                .'</td></tr>'
                .'</table>'
                .'<p style="margin:12px 0 0 0;text-align:center;font-size:12px;color:#64748b;">Creating magical moments, one painted smile at a time.</p>'
                .'</td></tr></table></body></html>';

            Mail::to($quote->email, $quote->name ?: 'Valued Client')->send(new StyledHtmlMail(
                sprintf('Quote Time Update%s', $quote->event_type ? ' - '.$quote->event_type : ''),
                $htmlContent,
                implode("\n", array_filter([
                    'Quote Time Update',
                    '=================',
                    '',
                    'Hi '.($quote->name ?: 'there').',',
                    '',
                    'Thank you for your quote request.',
                    'We are unavailable at the requested time, so we cannot confirm this booking yet.',
                    '',
                    'Event: '.$eventType,
                    'Date: '.$eventDate,
                    'Requested Start: '.$startTime,
                    'Requested End: '.$endTime,
                    'Reason: '.$reason,
                    '',
                    'If another time works for you, suggest a new time using the link below.',
                    $suggestTimeUrl ? 'Suggest a Different Time: '.$suggestTimeUrl : null,
                    $contactEmail !== '' ? 'Contact: '.$contactEmail : null,
                ])),
            ));
        } catch (\Throwable) {
            // Do not fail decline flow if customer email cannot be sent.
        }
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
            'phone' => ['nullable', 'string', 'max:48'],
            'guest_count' => ['nullable', 'integer', 'min:1', 'max:500'],
            'package_name' => ['nullable', 'string', 'max:120'],
            'services_requested' => ['nullable', 'array', 'max:20'],
            'services_requested.*' => ['string', 'max:80'],
            'travel_area' => ['nullable', 'string', 'max:255'],
            'venue_type' => ['nullable', Rule::in(['indoor', 'outdoor', 'mixed', 'unsure'])],
            'heard_about' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'terms_accepted' => ['nullable', 'boolean'],
            'anonymous_id' => ['nullable', 'string', 'max:80'],
            'event_type' => ['nullable', 'string', 'max:255'],
            'event_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_with:end_time'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'total_hours' => ['nullable', 'numeric', 'min:0'],
            'calc_payment_type' => ['nullable', Rule::in(['hourly', 'perface', 'package'])],
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
            'phone' => $this->sanitizePhone($validated['phone'] ?? null),
            'guest_count' => $this->nullableInt($validated, 'guest_count'),
            'package_name' => $this->nullableTrimmedString($validated, 'package_name', 120),
            'services_requested' => $this->normalizedServiceList($validated['services_requested'] ?? null),
            'travel_area' => $this->nullableTrimmedString($validated, 'travel_area'),
            'venue_type' => $this->nullableTrimmedString($validated, 'venue_type', 32),
            'heard_about' => $this->nullableTrimmedString($validated, 'heard_about', 120),
            'notes' => $this->nullableTrimmedString($validated, 'notes', 4000),
            'terms_accepted' => (bool) ($validated['terms_accepted'] ?? false),
            'terms_accepted_at' => ($validated['terms_accepted'] ?? false) ? now() : null,
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

    private function nullableInt(array $validated, string $key): ?int
    {
        if (! array_key_exists($key, $validated) || $validated[$key] === null || $validated[$key] === '') {
            return null;
        }

        return (int) $validated[$key];
    }

    private function nullableTrimmedString(array $validated, string $key, int $maxLength = 255): ?string
    {
        if (! array_key_exists($key, $validated) || $validated[$key] === null) {
            return null;
        }

        $value = trim((string) $validated[$key]);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $maxLength);
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

    /**
     * @param  array<int, string>|null  $services
     * @return array<int, string>|null
     */
    private function normalizedServiceList(?array $services): ?array
    {
        if (! is_array($services) || $services === []) {
            return null;
        }

        $normalized = collect($services)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->map(fn (string $value): string => mb_substr($value, 0, 80))
            ->unique()
            ->values()
            ->all();

        return $normalized === [] ? null : $normalized;
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
        $phone = $quote->phone ?: 'To be confirmed';
        $guestCount = $quote->guest_count !== null ? (string) $quote->guest_count : 'To be confirmed';
        $packageName = $quote->package_name ?: 'To be confirmed';
        $servicesRequested = $this->formatServices($quote->services_requested);
        $addOnSummary = $this->formatAddOnSummary($quote->services_requested);
        $addOnTotal = $this->addOnTotalFromServices($quote->services_requested);
        $travelArea = $quote->travel_area ?: 'To be confirmed';
        $venueType = $this->formatVenueType($quote->venue_type);
        $heardAbout = $quote->heard_about ?: 'To be confirmed';
        $notes = $quote->notes ? nl2br(e($quote->notes)) : '—';
        $termsAccepted = $quote->terms_accepted ? 'Yes' : 'No';
        $termsAcceptedAt = $quote->terms_accepted_at?->format('Y-m-d H:i') ?: '—';
        $confirmUrl = $actionUrls['confirm'] ?? null;
        $openTrackingUrl = $actionUrls['open'] ?? null;
        $termsUrl = rtrim((string) config('app.url', ''), '/').'/terms-and-conditions';

        $calculationRows = implode('', array_filter([
            $this->quoteEmailRow('Payment Type', $this->paymentTypeLabel($quote->calc_payment_type)),
            $this->quoteEmailRow('Base', $this->formatCurrency($quote->calc_base_amount)),
            $addOnSummary !== null ? $this->quoteEmailRow('Add-ons', e($addOnSummary)) : null,
            $addOnTotal !== null ? $this->quoteEmailRow('Add-on Total', $this->formatCurrency($addOnTotal)) : null,
            $this->quoteEmailRow('Setup', $this->formatCurrency($quote->calc_setup_amount)),
            $this->quoteEmailRow('Travel', $this->formatCurrency($quote->calc_travel_amount)),
            $this->quoteEmailRow('Subtotal', $this->formatCurrency($quote->calc_subtotal)),
            $this->shouldShowGst($quote->calc_gst_amount) ? $this->quoteEmailRow('GST', $this->formatCurrency($quote->calc_gst_amount)) : null,
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
            .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Duration</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($hours).'</td></tr>'
            .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Phone</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($phone).'</td></tr>'
            .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Guest Count</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($guestCount).'</td></tr>'
            .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Package</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($packageName).'</td></tr>'
            .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Services</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($servicesRequested).'</td></tr>'
            .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Travel Area</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($travelArea).'</td></tr>'
            .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Venue Type</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($venueType).'</td></tr>'
            .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Heard About Us</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($heardAbout).'</td></tr>'
            .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Location</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($quote->address ?: 'To be confirmed').'</td></tr>'
            .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Terms Accepted</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.e($termsAccepted).' ('.e($termsAcceptedAt).')</td></tr>'
            .'<tr><td style="padding:8px 0;color:#475569;vertical-align:top;">Notes</td><td style="padding:8px 0;text-align:right;color:#0f172a;font-weight:700;">'.$notes.'</td></tr>'
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
            .'<p style="margin:12px 0 0 0;font-size:12px;line-height:1.6;color:#475569;">By confirming this quote, you agree to our <a href="'.e($termsUrl).'" style="color:#0f766e;">Terms and Conditions</a>.</p>'
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
        $phone = $quote->phone ?: 'To be confirmed';
        $guestCount = $quote->guest_count !== null ? (string) $quote->guest_count : 'To be confirmed';
        $packageName = $quote->package_name ?: 'To be confirmed';
        $servicesRequested = $this->formatServices($quote->services_requested);
        $addOnSummary = $this->formatAddOnSummary($quote->services_requested);
        $addOnTotal = $this->addOnTotalFromServices($quote->services_requested);
        $travelArea = $quote->travel_area ?: 'To be confirmed';
        $venueType = $this->formatVenueType($quote->venue_type);
        $heardAbout = $quote->heard_about ?: 'To be confirmed';
        $notes = $quote->notes ?: '—';
        $termsAccepted = $quote->terms_accepted ? 'Yes' : 'No';
        $termsAcceptedAt = $quote->terms_accepted_at?->format('Y-m-d H:i') ?: '—';
        $contactEmail = (string) config('mail.from.address', '');
        $confirmUrl = $actionUrls['confirm'] ?? '';
        $termsUrl = rtrim((string) config('app.url', ''), '/').'/terms-and-conditions';

        $lines = array_values(array_filter([
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
            'Phone: '.$phone,
            'Guest Count: '.$guestCount,
            'Package: '.$packageName,
            'Services: '.$servicesRequested,
            'Travel Area: '.$travelArea,
            'Venue Type: '.$venueType,
            'Heard About Us: '.$heardAbout,
            'Location: '.($quote->address ?: 'To be confirmed'),
            'Terms Accepted: '.$termsAccepted.' ('.$termsAcceptedAt.')',
            'Notes: '.$notes,
            '',
            'Quote Breakdown',
            '---------------',
            'Payment Type: '.$this->paymentTypeLabel($quote->calc_payment_type),
            'Base: '.$this->formatCurrency($quote->calc_base_amount),
            $addOnSummary !== null ? 'Add-ons: '.$addOnSummary : null,
            $addOnTotal !== null ? 'Add-on Total: '.$this->formatCurrency($addOnTotal) : null,
            'Setup: '.$this->formatCurrency($quote->calc_setup_amount),
            'Travel: '.$this->formatCurrency($quote->calc_travel_amount),
            'Subtotal: '.$this->formatCurrency($quote->calc_subtotal),
            $this->shouldShowGst($quote->calc_gst_amount) ? 'GST: '.$this->formatCurrency($quote->calc_gst_amount) : null,
            'Total: '.$this->formatCurrency($quote->calc_total_amount),
            '',
            'Ready to confirm?',
        ], static fn (mixed $line): bool => $line !== null));

        if ($confirmUrl !== '') {
            $lines[] = 'Confirm your quote: '.$confirmUrl;
        }

        $lines[] = 'Terms and Conditions: '.$termsUrl;

        if ($contactEmail !== '') {
            $lines[] = 'Questions? Contact us at: '.$contactEmail;
        }

        $lines[] = '';
        $lines[] = 'With sparkles,';
        $lines[] = 'Sprinkle Fairydust Face Painting';

        return implode("\n", $lines);
    }

    /**
     * Build branded HTML email for admin quote confirmation notifications.
     */
    private function quoteConfirmedAdminEmailHtml(Quote $quote): string
    {
        $logoUrl = rtrim((string) config('app.url', ''), '/').'/images/logo.png';
        $adminQuoteUrl = rtrim((string) config('app.url', ''), '/').'/admin/quotes';
        $confirmedAt = optional($quote->client_confirmed_at)->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s');
        $addOnSummary = $this->formatAddOnSummary($quote->services_requested);
        $addOnTotal = $this->addOnTotalFromServices($quote->services_requested);

        $snapshotRows = implode('', array_filter([
            $this->quoteEmailRow('Name', e($quote->name ?: '—')),
            $this->quoteEmailRow('Email', e($quote->email ?: '—')),
            $this->quoteEmailRow('Phone', e($quote->phone ?: '—')),
            $this->quoteEmailRow('Event Type', e($quote->event_type ?: 'Event')),
            $this->quoteEmailRow('Date', e($quote->event_date?->format('l, j F Y') ?: 'To be confirmed')),
            $this->quoteEmailRow('Start Time', e($this->formatTime($quote->start_time))),
            $this->quoteEmailRow('End Time', e($this->formatTime($quote->end_time))),
            $this->quoteEmailRow('Duration', e($quote->total_hours !== null ? number_format($quote->total_hours, 2).' hours' : 'To be confirmed')),
            $this->quoteEmailRow('Guest Count', e($quote->guest_count !== null ? (string) $quote->guest_count : 'To be confirmed')),
            $this->quoteEmailRow('Package', e($quote->package_name ?: 'To be confirmed')),
            $this->quoteEmailRow('Services', e($this->formatServices($quote->services_requested))),
            $this->quoteEmailRow('Travel Area', e($quote->travel_area ?: 'To be confirmed')),
            $this->quoteEmailRow('Venue Type', e($this->formatVenueType($quote->venue_type))),
            $this->quoteEmailRow('Location', e($quote->address ?: 'To be confirmed')),
            $this->quoteEmailRow('Confirmed At', e($confirmedAt)),
        ]));

        $calculationRows = implode('', array_filter([
            $this->quoteEmailRow('Payment Type', $this->paymentTypeLabel($quote->calc_payment_type)),
            $this->quoteEmailRow('Base', $this->formatCurrency($quote->calc_base_amount)),
            $addOnSummary !== null ? $this->quoteEmailRow('Add-ons', e($addOnSummary)) : null,
            $addOnTotal !== null ? $this->quoteEmailRow('Add-on Total', $this->formatCurrency($addOnTotal)) : null,
            $this->quoteEmailRow('Setup', $this->formatCurrency($quote->calc_setup_amount)),
            $this->quoteEmailRow('Travel', $this->formatCurrency($quote->calc_travel_amount)),
            $this->quoteEmailRow('Subtotal', $this->formatCurrency($quote->calc_subtotal)),
            $this->shouldShowGst($quote->calc_gst_amount) ? $this->quoteEmailRow('GST', $this->formatCurrency($quote->calc_gst_amount)) : null,
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
            .'<h1 style="margin:0;text-align:center;font-size:34px;line-height:1.15;color:#0c4a6e;">Quote Confirmed</h1>'
            .'<p style="margin:10px auto 0 auto;max-width:520px;text-align:center;font-size:14px;line-height:1.6;color:#155e75;">A client has confirmed their booking. Review the details below.</p>'
            .'</td></tr>'
            .'<tr><td style="padding:24px 26px 28px 26px;">'
            .'<p style="margin:0 0 15px 0;font-size:14px;line-height:1.7;color:#334155;">The quote has been confirmed and is now locked in as a booking.</p>'
            .'<div style="border:1px solid #bfdbfe;border-radius:16px;padding:15px 16px;background:#f8fbff;margin-bottom:14px;">'
            .'<p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#0369a1;font-weight:700;">Confirmed Booking</p>'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">'
            .$snapshotRows
            .'</table></div>'
            .'<div style="border:1px solid #99f6e4;border-radius:16px;padding:15px 16px;background:linear-gradient(180deg,#f8fffe 0%,#f0fdfa 100%);">'
            .'<p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#0f766e;font-weight:700;">Quote Breakdown</p>'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">'
            .$calculationRows
            .'</table>'
            .'</div>'
            .'<div style="margin-top:16px;border-radius:14px;padding:14px 16px;background:#eff6ff;border:1px dashed #7dd3fc;">'
            .'<p style="margin:0;font-size:13px;line-height:1.7;color:#1e3a8a;">Use the admin quotes page to follow up on logistics or next steps.</p>'
            .'</div>'
            .'<p style="margin:14px 0 0 0;"><a href="'.e($adminQuoteUrl).'" style="display:inline-block;background:linear-gradient(135deg,#0ea5e9,#14b8a6);color:#ecfeff;text-decoration:none;font-weight:700;padding:11px 16px;border-radius:999px;">Open Admin Quotes</a></p>'
            .'<p style="margin:18px 0 0 0;font-size:14px;color:#0f172a;">With sparkles,<br><strong>Sprinkle Fairydust Face Painting</strong></p>'
            .'</td></tr>'
            .'</table>'
            .'<p style="margin:12px 0 0 0;text-align:center;font-size:12px;color:#64748b;">Creating magical moments, one painted smile at a time.</p>'
            .'</td></tr></table></body></html>';
    }

    /**
     * Build plain text email for admin quote confirmation notifications.
     */
    private function quoteConfirmedAdminEmailText(Quote $quote): string
    {
        $adminQuoteUrl = rtrim((string) config('app.url', ''), '/').'/admin/quotes';
        $confirmedAt = optional($quote->client_confirmed_at)->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s');
        $addOnSummary = $this->formatAddOnSummary($quote->services_requested);
        $addOnTotal = $this->addOnTotalFromServices($quote->services_requested);

        return implode("\n", array_values(array_filter([
            'Quote Confirmed',
            '===============',
            '',
            'Name: '.($quote->name ?: '—'),
            'Email: '.($quote->email ?: '—'),
            'Phone: '.($quote->phone ?: '—'),
            'Event Type: '.($quote->event_type ?: 'Event'),
            'Date: '.($quote->event_date?->format('l, j F Y') ?: 'To be confirmed'),
            'Start Time: '.$this->formatTime($quote->start_time),
            'End Time: '.$this->formatTime($quote->end_time),
            'Duration: '.($quote->total_hours !== null ? number_format($quote->total_hours, 2).' hours' : 'To be confirmed'),
            'Guest Count: '.($quote->guest_count !== null ? (string) $quote->guest_count : 'To be confirmed'),
            'Package: '.($quote->package_name ?: 'To be confirmed'),
            'Services: '.$this->formatServices($quote->services_requested),
            'Travel Area: '.($quote->travel_area ?: 'To be confirmed'),
            'Venue Type: '.$this->formatVenueType($quote->venue_type),
            'Location: '.($quote->address ?: 'To be confirmed'),
            'Confirmed At: '.$confirmedAt,
            '',
            'Payment Type: '.$this->paymentTypeLabel($quote->calc_payment_type),
            'Base: '.$this->formatCurrency($quote->calc_base_amount),
            $addOnSummary !== null ? 'Add-ons: '.$addOnSummary : null,
            $addOnTotal !== null ? 'Add-on Total: '.$this->formatCurrency($addOnTotal) : null,
            'Setup: '.$this->formatCurrency($quote->calc_setup_amount),
            'Travel: '.$this->formatCurrency($quote->calc_travel_amount),
            'Subtotal: '.$this->formatCurrency($quote->calc_subtotal),
            $this->shouldShowGst($quote->calc_gst_amount) ? 'GST: '.$this->formatCurrency($quote->calc_gst_amount) : null,
            'Total: '.$this->formatCurrency($quote->calc_total_amount),
            '',
            'Open Admin Quotes: '.$adminQuoteUrl,
        ], static fn (mixed $line): bool => $line !== null)));
    }

    /**
     * Build branded HTML email for client quote confirmation receipts.
     */
    private function quoteConfirmedClientEmailHtml(Quote $quote): string
    {
        $logoUrl = rtrim((string) config('app.url', ''), '/').'/images/logo.png';
        $contactEmail = (string) config('mail.from.address', '');
        $confirmedAt = optional($quote->client_confirmed_at)->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s');
        $addOnSummary = $this->formatAddOnSummary($quote->services_requested);
        $addOnTotal = $this->addOnTotalFromServices($quote->services_requested);

        $snapshotRows = implode('', array_filter([
            $this->quoteEmailRow('Event Type', e($quote->event_type ?: 'your event')),
            $this->quoteEmailRow('Date', e($quote->event_date?->format('l, j F Y') ?: 'To be confirmed')),
            $this->quoteEmailRow('Start Time', e($this->formatTime($quote->start_time))),
            $this->quoteEmailRow('End Time', e($this->formatTime($quote->end_time))),
            $this->quoteEmailRow('Duration', e($quote->total_hours !== null ? number_format($quote->total_hours, 2).' hours' : 'To be confirmed')),
            $this->quoteEmailRow('Guest Count', e($quote->guest_count !== null ? (string) $quote->guest_count : 'To be confirmed')),
            $this->quoteEmailRow('Package', e($quote->package_name ?: 'To be confirmed')),
            $this->quoteEmailRow('Services', e($this->formatServices($quote->services_requested))),
            $this->quoteEmailRow('Travel Area', e($quote->travel_area ?: 'To be confirmed')),
            $this->quoteEmailRow('Venue Type', e($this->formatVenueType($quote->venue_type))),
            $this->quoteEmailRow('Location', e($quote->address ?: 'To be confirmed')),
            $this->quoteEmailRow('Confirmed At', e($confirmedAt)),
        ]));

        $calculationRows = implode('', array_filter([
            $this->quoteEmailRow('Payment Type', $this->paymentTypeLabel($quote->calc_payment_type)),
            $this->quoteEmailRow('Base', $this->formatCurrency($quote->calc_base_amount)),
            $addOnSummary !== null ? $this->quoteEmailRow('Add-ons', e($addOnSummary)) : null,
            $addOnTotal !== null ? $this->quoteEmailRow('Add-on Total', $this->formatCurrency($addOnTotal)) : null,
            $this->quoteEmailRow('Setup', $this->formatCurrency($quote->calc_setup_amount)),
            $this->quoteEmailRow('Travel', $this->formatCurrency($quote->calc_travel_amount)),
            $this->quoteEmailRow('Subtotal', $this->formatCurrency($quote->calc_subtotal)),
            $this->shouldShowGst($quote->calc_gst_amount) ? $this->quoteEmailRow('GST', $this->formatCurrency($quote->calc_gst_amount)) : null,
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
            .'<h1 style="margin:0;text-align:center;font-size:34px;line-height:1.15;color:#0c4a6e;">Quote Confirmed</h1>'
            .'<p style="margin:10px auto 0 auto;max-width:520px;text-align:center;font-size:14px;line-height:1.6;color:#155e75;">Your booking is confirmed and ready for a magical event day.</p>'
            .'</td></tr>'
            .'<tr><td style="padding:24px 26px 28px 26px;">'
            .'<p style="margin:0 0 12px 0;font-size:16px;color:#0f172a;">Hi '.e($quote->name ?: 'there').',</p>'
            .'<p style="margin:0 0 15px 0;font-size:14px;line-height:1.7;color:#334155;">Thanks for confirming your quote with Sprinkle Fairydust. Your booking is now marked as confirmed.</p>'
            .'<div style="border:1px solid #bfdbfe;border-radius:16px;padding:15px 16px;background:#f8fbff;margin-bottom:14px;">'
            .'<p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#0369a1;font-weight:700;">Event Snapshot</p>'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">'
            .$snapshotRows
            .'</table></div>'
            .'<div style="border:1px solid #99f6e4;border-radius:16px;padding:15px 16px;background:linear-gradient(180deg,#f8fffe 0%,#f0fdfa 100%);">'
            .'<p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#0f766e;font-weight:700;">Quote Breakdown</p>'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">'
            .$calculationRows
            .'</table>'
            .'</div>'
            .'<div style="margin-top:16px;border-radius:14px;padding:14px 16px;background:#eff6ff;border:1px dashed #7dd3fc;">'
            .'<p style="margin:0;font-size:13px;line-height:1.7;color:#1e3a8a;">We are excited to bring the sparkle to your event.</p>'
            .'</div>'
            .($contactEmail !== ''
                ? '<p style="margin:10px 0 0 0;font-size:12px;color:#64748b;">Questions? Reply to this email or contact us at '.e($contactEmail).'.</p>'
                : '<p style="margin:10px 0 0 0;font-size:12px;color:#64748b;">Questions? Reply to this email and we can help.</p>')
            .'<p style="margin:18px 0 0 0;font-size:14px;color:#0f172a;">With sparkles,<br><strong>Sprinkle Fairydust Face Painting</strong></p>'
            .'</td></tr>'
            .'</table>'
            .'<p style="margin:12px 0 0 0;text-align:center;font-size:12px;color:#64748b;">Creating magical moments, one painted smile at a time.</p>'
            .'</td></tr></table></body></html>';
    }

    /**
     * Build plain text email for client quote confirmation receipts.
     */
    private function quoteConfirmedClientEmailText(Quote $quote): string
    {
        $contactEmail = (string) config('mail.from.address', '');
        $confirmedAt = optional($quote->client_confirmed_at)->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s');
        $addOnSummary = $this->formatAddOnSummary($quote->services_requested);
        $addOnTotal = $this->addOnTotalFromServices($quote->services_requested);

        return implode("\n", array_values(array_filter([
            'Quote Confirmed',
            '===============',
            '',
            'Hi '.($quote->name ?: 'there').',',
            '',
            'Thanks for confirming your quote with Sprinkle Fairydust.',
            'Your booking is now marked as confirmed.',
            '',
            'Event Type: '.($quote->event_type ?: 'your event'),
            'Date: '.($quote->event_date?->format('l, j F Y') ?: 'To be confirmed'),
            'Start: '.$this->formatTime($quote->start_time),
            'End: '.$this->formatTime($quote->end_time),
            'Duration: '.($quote->total_hours !== null ? number_format($quote->total_hours, 2).' hours' : 'To be confirmed'),
            'Guest Count: '.($quote->guest_count !== null ? (string) $quote->guest_count : 'To be confirmed'),
            'Package: '.($quote->package_name ?: 'To be confirmed'),
            'Services: '.$this->formatServices($quote->services_requested),
            'Travel Area: '.($quote->travel_area ?: 'To be confirmed'),
            'Venue Type: '.$this->formatVenueType($quote->venue_type),
            'Location: '.($quote->address ?: 'To be confirmed'),
            'Confirmed At: '.$confirmedAt,
            '',
            'Payment Type: '.$this->paymentTypeLabel($quote->calc_payment_type),
            'Base: '.$this->formatCurrency($quote->calc_base_amount),
            $addOnSummary !== null ? 'Add-ons: '.$addOnSummary : null,
            $addOnTotal !== null ? 'Add-on Total: '.$this->formatCurrency($addOnTotal) : null,
            'Setup: '.$this->formatCurrency($quote->calc_setup_amount),
            'Travel: '.$this->formatCurrency($quote->calc_travel_amount),
            'Subtotal: '.$this->formatCurrency($quote->calc_subtotal),
            $this->shouldShowGst($quote->calc_gst_amount) ? 'GST: '.$this->formatCurrency($quote->calc_gst_amount) : null,
            'Total: '.$this->formatCurrency($quote->calc_total_amount),
            '',
            'We are excited to bring the sparkle to your event.',
            $contactEmail !== '' ? 'Questions? Contact us at: '.$contactEmail : null,
            '',
            'With sparkles,',
            'Sprinkle Fairydust Face Painting',
        ], static fn (mixed $line): bool => $line !== null)));
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

    /**
     * Build signed action URLs used in quote decline and suggested-time flows.
     *
     * @return array{suggest: string, submit: string}
     */
    private function quoteSuggestedTimeActionUrls(Quote $quote): array
    {
        $expiresAt = now()->addDays((int) config('services.sprinkle.quote_link_expiry_days', 45));

        return [
            'suggest' => URL::temporarySignedRoute('quotes.suggest-time', $expiresAt, ['quote' => $quote->id]),
            'submit' => URL::temporarySignedRoute('quotes.suggest-time.submit', $expiresAt, ['quote' => $quote->id]),
        ];
    }

    private function quoteEmailRow(string $label, string $value): string
    {
        return '<tr>'
            .'<td style="padding:7px 0;border-bottom:1px solid #e2e8f0;color:#475569;">'.e($label).'</td>'
            .'<td style="padding:7px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;">'.$value.'</td>'
            .'</tr>';
    }

    /**
     * Build the signed form page for suggesting a different quote time.
     *
     * @param  array{event_date?: string, start_time?: string, end_time?: string, notes?: string}  $values
     */
    private function suggestedTimeFormHtml(Quote $quote, string $submitUrl, array $values = [], ?string $errorMessage = null): string
    {
        $logoUrl = rtrim((string) config('app.url', ''), '/').'/images/logo.png';
        $eventType = $quote->event_type ?: 'Event';
        $requestedDate = $quote->event_date?->format('l, j F Y') ?: 'To be confirmed';
        $requestedStart = $this->formatTime($quote->start_time);
        $requestedEnd = $this->formatTime($quote->end_time);

        return sprintf(
            '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>'
                .'<body style="margin:0;padding:0;background:linear-gradient(180deg,#e0f2fe 0%%,#ecfeff 55%%,#f0f9ff 100%%);font-family:Quicksand,Arial,sans-serif;color:#0f172a;">'
                .'<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="padding:24px 12px;"><tr><td align="center">'
                .'<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="max-width:680px;border-radius:24px;overflow:hidden;border:1px solid #bae6fd;background:#ffffff;box-shadow:0 24px 44px rgba(14,116,144,0.18);">'
                .'<tr><td style="padding:12px 20px;background:#0c4a6e;text-align:center;">'
                .'<p style="margin:0;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#cffafe;font-weight:700;">Sprinkle Fairydust Face Painting</p>'
                .'</td></tr>'
                .'<tr><td style="padding:28px 24px 24px;background:linear-gradient(135deg,#dbeafe 0%%,#ccfbf1 52%%,#e0f2fe 100%%);text-align:center;">'
                .'<img src="%s" alt="Sprinkle Fairydust" style="display:block;width:124px;max-width:100%%;height:auto;margin:0 auto 12px auto;">'
                .'<h1 style="margin:0;font-size:32px;line-height:1.2;color:#0c4a6e;">Suggest a Different Time</h1>'
                .'<p style="margin:10px auto 0 auto;max-width:520px;font-size:14px;line-height:1.6;color:#155e75;">Share your new preferred date and time and we will review availability.</p>'
                .'</td></tr>'
                .'<tr><td style="padding:22px 24px 28px 24px;">'
                .'<div style="border:1px solid #bfdbfe;border-radius:16px;padding:15px 16px;background:#f8fbff;margin-bottom:14px;">'
                .'<p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#0369a1;font-weight:700;">Original Request</p>'
                .'<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">'
                .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Event</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">%s</td></tr>'
                .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Date</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">%s</td></tr>'
                .'<tr><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">Start</td><td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">%s</td></tr>'
                .'<tr><td style="padding:8px 0;color:#475569;">End</td><td style="padding:8px 0;text-align:right;color:#0f172a;font-weight:700;">%s</td></tr>'
                .'</table></div>'
                .'%s'
                .'<form method="POST" action="%s" style="display:block;">'
                .'<label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">Preferred Date</label>'
                .'<input name="event_date" type="date" value="%s" required style="width:100%%;box-sizing:border-box;margin:0 0 12px 0;padding:11px 12px;border-radius:10px;border:1px solid #cbd5e1;font-size:14px;">'
                .'<label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">Preferred Start Time</label>'
                .'<input name="start_time" type="time" value="%s" required style="width:100%%;box-sizing:border-box;margin:0 0 12px 0;padding:11px 12px;border-radius:10px;border:1px solid #cbd5e1;font-size:14px;">'
                .'<label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">Preferred End Time</label>'
                .'<input name="end_time" type="time" value="%s" required style="width:100%%;box-sizing:border-box;margin:0 0 12px 0;padding:11px 12px;border-radius:10px;border:1px solid #cbd5e1;font-size:14px;">'
                .'<label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">Notes (optional)</label>'
                .'<textarea name="notes" rows="4" style="width:100%%;box-sizing:border-box;margin:0 0 14px 0;padding:11px 12px;border-radius:10px;border:1px solid #cbd5e1;font-size:14px;resize:vertical;">%s</textarea>'
                .'<button type="submit" style="display:inline-block;background:linear-gradient(135deg,#0ea5e9,#14b8a6);color:#ecfeff;text-decoration:none;font-weight:700;padding:11px 16px;border-radius:999px;border:0;cursor:pointer;">Send New Time Suggestion</button>'
                .'</form>'
                .'<p style="margin:14px 0 0 0;font-size:12px;color:#64748b;">This secure link can expire automatically for your privacy.</p>'
                .'</td></tr>'
                .'</table>'
                .'<p style="margin:12px 0 0 0;text-align:center;font-size:12px;color:#64748b;">Creating magical moments, one painted smile at a time.</p>'
                .'</td></tr></table></body></html>',
            e($logoUrl),
            e($eventType),
            e($requestedDate),
            e($requestedStart),
            e($requestedEnd),
            $errorMessage !== null && trim($errorMessage) !== ''
                ? '<p style="margin:0 0 12px 0;padding:10px 12px;border-radius:10px;border:1px solid #fecdd3;background:#fff1f2;color:#9f1239;font-size:13px;font-weight:700;">'.e($errorMessage).'</p>'
                : '',
            e($submitUrl),
            e((string) ($values['event_date'] ?? '')),
            e((string) ($values['start_time'] ?? '')),
            e((string) ($values['end_time'] ?? '')),
            e((string) ($values['notes'] ?? '')),
        );
    }

    /**
     * Build a signed-flow status page shown after a client time suggestion.
     */
    private function suggestedTimeSubmittedHtml(string $title, string $message): string
    {
        $logoUrl = rtrim((string) config('app.url', ''), '/').'/images/logo.png';

        return sprintf(
            '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>'
                .'<body style="margin:0;padding:0;background:linear-gradient(180deg,#e0f2fe 0%%,#ecfeff 55%%,#f0f9ff 100%%);font-family:Quicksand,Arial,sans-serif;color:#0f172a;">'
                .'<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="padding:30px 14px;"><tr><td align="center">'
                .'<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="max-width:620px;border-radius:24px;overflow:hidden;border:1px solid #bae6fd;background:#ffffff;box-shadow:0 24px 44px rgba(14,116,144,0.18);">'
                .'<tr><td style="padding:28px 24px 24px;background:radial-gradient(circle at top right,#99f6e4 0%%,#bae6fd 46%%,#dbeafe 100%%);text-align:center;">'
                .'<p style="margin:0 0 8px 0;font-size:11px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:#0f766e;">Sprinkle Fairydust Face Painting</p>'
                .'<img src="%s" alt="Sprinkle Fairydust" style="display:block;width:120px;max-width:100%%;height:auto;margin:0 auto 12px auto;">'
                .'<h1 style="margin:0;font-size:32px;line-height:1.2;color:#0c4a6e;">%s</h1>'
                .'</td></tr>'
                .'<tr><td style="padding:24px 24px 26px 24px;">'
                .'<div style="border:1px solid #bfdbfe;border-radius:16px;padding:16px 16px 14px 16px;background:linear-gradient(180deg,#f8fbff 0%%,#f0fdfa 100%%);">'
                .'<p style="margin:0;font-size:16px;line-height:1.6;color:#0f172a;">%s</p>'
                .'</div>'
                .'<p style="margin:18px 0 0 0;font-size:14px;color:#0f172a;">With sparkles,<br><strong>Sprinkle Fairydust Face Painting</strong></p>'
                .'</td></tr>'
                .'</table>'
                .'<p style="margin:14px 0 0 0;text-align:center;font-size:12px;color:#64748b;">Creating magical moments, one painted smile at a time.</p>'
                .'</td></tr></table></body></html>',
            e($logoUrl),
            e($title),
            e($message),
        );
    }

    /**
     * Notify admin when a client suggests a different time.
     */
    private function sendSuggestedTimeNotificationToAdmin(Quote $quote): void
    {
        $toEmail = config('services.sprinkle.quote_reschedule_notification_email')
            ?: config('services.sprinkle.quote_notification_email')
            ?: 'brettj@dekode.co.nz';

        if (! is_string($toEmail) || trim($toEmail) === '') {
            return;
        }

        $adminQuoteUrl = rtrim((string) config('app.url', ''), '/').'/admin/quotes';
        $suggestedDate = $quote->client_suggested_event_date?->format('l, j F Y') ?: '—';
        $suggestedStart = $this->formatTime($quote->client_suggested_start_time);
        $suggestedEnd = $this->formatTime($quote->client_suggested_end_time);

        try {
            Mail::to($toEmail, 'Sprinkle Fairydust Admin')->send(new StyledHtmlMail(
                sprintf('New Time Suggested: %s', $quote->name ?: 'Client'),
                $this->suggestedTimeAdminEmailHtml($quote, $adminQuoteUrl),
                $this->suggestedTimeAdminEmailText($quote, $adminQuoteUrl),
            ));
        } catch (\Throwable) {
            // Do not fail request if admin notification cannot be sent.
        }
    }

    /**
     * Send confirmation to client after they submit a new preferred time.
     */
    private function sendSuggestedTimeReceiptToClient(Quote $quote): void
    {
        if (! $quote->email) {
            return;
        }

        $contactEmail = (string) config('mail.from.address', '');

        try {
            Mail::to($quote->email, $quote->name ?: 'Valued Client')->send(new StyledHtmlMail(
                sprintf('We Received Your New Time%s', $quote->event_type ? ' - '.$quote->event_type : ''),
                $this->suggestedTimeReceiptEmailHtml($quote, $contactEmail),
                $this->suggestedTimeReceiptEmailText($quote, $contactEmail),
            ));
        } catch (\Throwable) {
            // Do not fail request if customer receipt email cannot be sent.
        }
    }

    private function suggestedTimeAdminEmailHtml(Quote $quote, string $adminQuoteUrl): string
    {
        $logoUrl = rtrim((string) config('app.url', ''), '/').'/images/logo.png';
        $suggestedDate = $quote->client_suggested_event_date?->format('l, j F Y') ?: '—';
        $suggestedStart = $this->formatTime($quote->client_suggested_start_time);
        $suggestedEnd = $this->formatTime($quote->client_suggested_end_time);
        $submittedAt = optional($quote->client_suggested_time_at)->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s');

        $snapshotRows = implode('', [
            $this->quoteEmailRow('Name', e($quote->name ?: '—')),
            $this->quoteEmailRow('Email', e($quote->email ?: '—')),
            $this->quoteEmailRow('Phone', e($quote->phone ?: '—')),
            $this->quoteEmailRow('Event', e($quote->event_type ?: '—')),
            $this->quoteEmailRow('Suggested Date', e($suggestedDate)),
            $this->quoteEmailRow('Suggested Start', e($suggestedStart)),
            $this->quoteEmailRow('Suggested End', e($suggestedEnd)),
            $this->quoteEmailRow('Submitted At', e($submittedAt)),
            $this->quoteEmailRow('Notes', e($quote->client_suggested_time_notes ?: '—')),
        ]);

        return '<!doctype html><html><head><meta charset="utf-8"></head><body style="margin:0;padding:0;background:#eaf7ff;font-family:Quicksand,Arial,sans-serif;color:#0f172a;">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:radial-gradient(circle at top,#bfdbfe 0%,#ccfbf1 45%,#f0f9ff 100%);padding:30px 12px;"><tr><td align="center">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:660px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #bae6fd;box-shadow:0 24px 44px rgba(14,116,144,0.16);">'
            .'<tr><td style="padding:12px 20px;background:#0c4a6e;text-align:center;">'
            .'<p style="margin:0;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#cffafe;font-weight:700;">Sprinkle Fairydust Face Painting</p>'
            .'</td></tr>'
            .'<tr><td style="padding:28px 26px 24px;background:linear-gradient(135deg,#dbeafe 0%,#ccfbf1 52%,#e0f2fe 100%);">'
            .'<img src="'.e($logoUrl).'" alt="Sprinkle Fairydust" style="width:128px;height:auto;display:block;margin:0 auto 12px auto;">'
            .'<h1 style="margin:0;text-align:center;font-size:34px;line-height:1.15;color:#0c4a6e;">Client Suggested a New Time</h1>'
            .'<p style="margin:10px auto 0 auto;max-width:520px;text-align:center;font-size:14px;line-height:1.6;color:#155e75;">Review the client’s preferred replacement time and follow up from admin.</p>'
            .'</td></tr>'
            .'<tr><td style="padding:24px 26px 28px 26px;">'
            .'<div style="border:1px solid #bfdbfe;border-radius:16px;padding:15px 16px;background:#f8fbff;margin-bottom:14px;">'
            .'<p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#0369a1;font-weight:700;">New Suggested Time</p>'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">'
            .$snapshotRows
            .'</table></div>'
            .'<p style="margin:14px 0 0 0;"><a href="'.e($adminQuoteUrl).'" style="display:inline-block;background:linear-gradient(135deg,#0ea5e9,#14b8a6);color:#ecfeff;text-decoration:none;font-weight:700;padding:11px 16px;border-radius:999px;">Open Admin Quotes</a></p>'
            .'<p style="margin:18px 0 0 0;font-size:14px;color:#0f172a;">With sparkles,<br><strong>Sprinkle Fairydust Face Painting</strong></p>'
            .'</td></tr>'
            .'</table>'
            .'<p style="margin:12px 0 0 0;text-align:center;font-size:12px;color:#64748b;">Creating magical moments, one painted smile at a time.</p>'
            .'</td></tr></table></body></html>';
    }

    private function suggestedTimeAdminEmailText(Quote $quote, string $adminQuoteUrl): string
    {
        $suggestedDate = $quote->client_suggested_event_date?->format('l, j F Y') ?: '—';
        $suggestedStart = $this->formatTime($quote->client_suggested_start_time);
        $suggestedEnd = $this->formatTime($quote->client_suggested_end_time);
        $submittedAt = optional($quote->client_suggested_time_at)->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s');

        return implode("\n", [
            'Client Suggested a New Time',
            '==========================',
            'Name: '.($quote->name ?: '—'),
            'Email: '.($quote->email ?: '—'),
            'Phone: '.($quote->phone ?: '—'),
            'Event: '.($quote->event_type ?: '—'),
            'Suggested Date: '.$suggestedDate,
            'Suggested Start: '.$suggestedStart,
            'Suggested End: '.$suggestedEnd,
            'Notes: '.($quote->client_suggested_time_notes ?: '—'),
            'Submitted At: '.$submittedAt,
            '',
            'Admin Quotes: '.$adminQuoteUrl,
        ]);
    }

    private function suggestedTimeReceiptEmailHtml(Quote $quote, string $contactEmail): string
    {
        $logoUrl = rtrim((string) config('app.url', ''), '/').'/images/logo.png';
        $suggestedDate = $quote->client_suggested_event_date?->format('l, j F Y') ?: 'To be confirmed';
        $suggestedStart = $this->formatTime($quote->client_suggested_start_time);
        $suggestedEnd = $this->formatTime($quote->client_suggested_end_time);
        $submittedAt = optional($quote->client_suggested_time_at)->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s');

        $snapshotRows = implode('', [
            $this->quoteEmailRow('Event', e($quote->event_type ?: '—')),
            $this->quoteEmailRow('Suggested Date', e($suggestedDate)),
            $this->quoteEmailRow('Suggested Start', e($suggestedStart)),
            $this->quoteEmailRow('Suggested End', e($suggestedEnd)),
            $this->quoteEmailRow('Submitted At', e($submittedAt)),
            $this->quoteEmailRow('Notes', e($quote->client_suggested_time_notes ?: '—')),
        ]);

        return '<!doctype html><html><head><meta charset="utf-8"></head><body style="margin:0;padding:0;background:#eaf7ff;font-family:Quicksand,Arial,sans-serif;color:#0f172a;">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:radial-gradient(circle at top,#bfdbfe 0%,#ccfbf1 45%,#f0f9ff 100%);padding:30px 12px;"><tr><td align="center">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:660px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #bae6fd;box-shadow:0 24px 44px rgba(14,116,144,0.16);">'
            .'<tr><td style="padding:12px 20px;background:#0c4a6e;text-align:center;">'
            .'<p style="margin:0;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#cffafe;font-weight:700;">Sprinkle Fairydust Face Painting</p>'
            .'</td></tr>'
            .'<tr><td style="padding:28px 26px 24px;background:linear-gradient(135deg,#dbeafe 0%,#ccfbf1 52%,#e0f2fe 100%);">'
            .'<img src="'.e($logoUrl).'" alt="Sprinkle Fairydust" style="width:128px;height:auto;display:block;margin:0 auto 12px auto;">'
            .'<h1 style="margin:0;text-align:center;font-size:34px;line-height:1.15;color:#0c4a6e;">New Time Suggestion Received</h1>'
            .'<p style="margin:10px auto 0 auto;max-width:520px;text-align:center;font-size:14px;line-height:1.6;color:#155e75;">Thanks for sharing a new preferred time. We will review availability and get back to you soon.</p>'
            .'</td></tr>'
            .'<tr><td style="padding:24px 26px 28px 26px;">'
            .'<p style="margin:0 0 12px 0;font-size:16px;color:#0f172a;">Hi '.e($quote->name ?: 'there').',</p>'
            .'<div style="border:1px solid #bfdbfe;border-radius:16px;padding:15px 16px;background:#f8fbff;margin-bottom:14px;">'
            .'<p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#0369a1;font-weight:700;">Your Suggested Time</p>'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">'
            .$snapshotRows
            .'</table></div>'
            .($contactEmail !== ''
                ? '<p style="margin:10px 0 0 0;font-size:12px;color:#64748b;">Need to adjust anything else? Reply to this email or contact us at '.e($contactEmail).'.</p>'
                : '<p style="margin:10px 0 0 0;font-size:12px;color:#64748b;">Need to adjust anything else? Reply to this email.</p>')
            .'<p style="margin:18px 0 0 0;font-size:14px;color:#0f172a;">With sparkles,<br><strong>Sprinkle Fairydust Face Painting</strong></p>'
            .'</td></tr>'
            .'</table>'
            .'<p style="margin:12px 0 0 0;text-align:center;font-size:12px;color:#64748b;">Creating magical moments, one painted smile at a time.</p>'
            .'</td></tr></table></body></html>';
    }

    private function suggestedTimeReceiptEmailText(Quote $quote, string $contactEmail): string
    {
        $suggestedDate = $quote->client_suggested_event_date?->format('l, j F Y') ?: 'To be confirmed';
        $suggestedStart = $this->formatTime($quote->client_suggested_start_time);
        $suggestedEnd = $this->formatTime($quote->client_suggested_end_time);

        return implode("\n", array_filter([
            'New Time Suggestion Received',
            '===========================',
            '',
            'Hi '.($quote->name ?: 'there').',',
            '',
            'Thanks for suggesting a different time.',
            'We have received your update and will review availability.',
            '',
            'Event: '.($quote->event_type ?: '—'),
            'Suggested Date: '.$suggestedDate,
            'Suggested Start: '.$suggestedStart,
            'Suggested End: '.$suggestedEnd,
            'Notes: '.($quote->client_suggested_time_notes ?: '—'),
            $contactEmail !== '' ? 'Contact: '.$contactEmail : null,
        ]));
    }

    /**
     * @param  array<int, string>|null  $services
     */
    private function formatServices(?array $services): string
    {
        if (! is_array($services) || $services === []) {
            return 'To be confirmed';
        }

        return implode(', ', $services);
    }

    /**
     * @param  array<int, string>|null  $services
     * @return array<int, array{name: string, amount: ?float}>
     */
    private function addOnsFromServices(?array $services): array
    {
        if (! is_array($services) || $services === []) {
            return [];
        }

        return collect($services)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->map(function (string $service): ?array {
                if (! preg_match('/^add-on:\s*(.+)$/i', $service, $matches)) {
                    return null;
                }

                $content = trim((string) ($matches[1] ?? ''));

                if ($content === '') {
                    return null;
                }

                if (preg_match('/^(.*)\s+\(\$(\d+(?:\.\d{1,2})?)\)\s*$/', $content, $amountMatch)) {
                    $name = trim((string) ($amountMatch[1] ?? ''));

                    if ($name === '') {
                        return null;
                    }

                    return [
                        'name' => $name,
                        'amount' => (float) $amountMatch[2],
                    ];
                }

                return [
                    'name' => $content,
                    'amount' => null,
                ];
            })
            ->filter(fn (?array $addOn): bool => $addOn !== null && $addOn['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>|null  $services
     */
    private function formatAddOnSummary(?array $services): ?string
    {
        $addOns = $this->addOnsFromServices($services);

        if ($addOns === []) {
            return null;
        }

        return collect($addOns)
            ->pluck('name')
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->implode(', ');
    }

    /**
     * @param  array<int, string>|null  $services
     */
    private function addOnTotalFromServices(?array $services): ?float
    {
        $amounts = collect($this->addOnsFromServices($services))
            ->pluck('amount')
            ->filter(fn (mixed $value): bool => $value !== null)
            ->map(fn (mixed $value): float => (float) $value)
            ->values();

        if ($amounts->isEmpty()) {
            return null;
        }

        return (float) $amounts->sum();
    }

    private function shouldShowGst(?float $gstAmount): bool
    {
        return $gstAmount !== null && $gstAmount > 0;
    }

    private function formatVenueType(?string $venueType): string
    {
        if (! $venueType) {
            return 'To be confirmed';
        }

        return match ($venueType) {
            'indoor' => 'Indoor',
            'outdoor' => 'Outdoor',
            'mixed' => 'Indoor + Outdoor',
            'unsure' => 'Not sure yet',
            default => ucfirst($venueType),
        };
    }

    private function paymentTypeLabel(?string $paymentType): string
    {
        if ($paymentType === 'hourly') {
            return 'Organizer-Paid (Hourly)';
        }

        if ($paymentType === 'perface') {
            return 'Pay Per Face';
        }

        if ($paymentType === 'package') {
            return 'Package';
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

    private function formatTimeInput(?string $time): string
    {
        if (! $time) {
            return '';
        }

        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Throwable) {
            return substr($time, 0, 5);
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
