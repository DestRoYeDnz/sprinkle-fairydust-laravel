<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\StyledHtmlMail;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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

        $this->sendNotificationEmail($testimonial);

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

    private function sendNotificationEmail(Testimonial $testimonial): void
    {
        $toEmail = config('services.sprinkle.testimonial_notification_email')
            ?: config('services.sprinkle.quote_notification_email')
            ?: 'brettj@dekode.co.nz';

        if (! $toEmail) {
            return;
        }

        $links = collect($testimonial->urls ?? [])
            ->map(fn (?string $url): string => $url ? '<li><a href="'.e($url).'">'.e($url).'</a></li>' : '')
            ->filter()
            ->implode('');

        $linksHtml = $links !== '' ? "<ul>{$links}</ul>" : '<p>—</p>';
        $adminTestimonialsUrl = rtrim((string) config('app.url', ''), '/').'/admin/testimonials';

        Mail::to($toEmail, 'Sprinkle Fairydust Admin')->send(new StyledHtmlMail(
            sprintf('New Testimonial Submission from %s', $testimonial->name),
            $this->testimonialNotificationEmailHtml($testimonial, $linksHtml, $adminTestimonialsUrl),
            $this->testimonialNotificationEmailText($testimonial, $adminTestimonialsUrl),
        ));
    }

    private function testimonialNotificationEmailHtml(Testimonial $testimonial, string $linksHtml, string $adminTestimonialsUrl): string
    {
        $logoUrl = rtrim((string) config('app.url', ''), '/').'/images/logo.png';
        $submittedAt = optional($testimonial->created_at)->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s');

        return '<!doctype html><html><head><meta charset="utf-8"></head><body style="margin:0;padding:0;background:#eaf7ff;font-family:Quicksand,Arial,sans-serif;color:#0f172a;">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:radial-gradient(circle at top,#bfdbfe 0%,#ccfbf1 45%,#f0f9ff 100%);padding:30px 12px;"><tr><td align="center">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:660px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #bae6fd;box-shadow:0 24px 44px rgba(14,116,144,0.16);">'
            .'<tr><td style="padding:12px 20px;background:#0c4a6e;text-align:center;">'
            .'<p style="margin:0;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#cffafe;font-weight:700;">Sprinkle Fairydust Face Painting</p>'
            .'</td></tr>'
            .'<tr><td style="padding:28px 26px 24px;background:linear-gradient(135deg,#dbeafe 0%,#ccfbf1 52%,#e0f2fe 100%);">'
            .'<img src="'.e($logoUrl).'" alt="Sprinkle Fairydust" style="width:128px;height:auto;display:block;margin:0 auto 12px auto;">'
            .'<h1 style="margin:0;text-align:center;font-size:34px;line-height:1.15;color:#0c4a6e;">New Testimonial Submission</h1>'
            .'<p style="margin:10px auto 0 auto;max-width:520px;text-align:center;font-size:14px;line-height:1.6;color:#155e75;">A new testimonial was submitted from the public site and is pending approval.</p>'
            .'</td></tr>'
            .'<tr><td style="padding:24px 26px 28px 26px;">'
            .'<div style="border:1px solid #bfdbfe;border-radius:16px;padding:15px 16px;background:#f8fbff;margin-bottom:14px;">'
            .'<p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#0369a1;font-weight:700;">Submission Snapshot</p>'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">'
            .$this->emailRow('Name', e($testimonial->name))
            .$this->emailRow('Submitted At', e($submittedAt))
            .'</table>'
            .'</div>'
            .'<div style="border:1px solid #99f6e4;border-radius:16px;padding:15px 16px;background:linear-gradient(180deg,#f8fffe 0%,#f0fdfa 100%);">'
            .'<p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#0f766e;font-weight:700;">Testimonial</p>'
            .'<p style="margin:0;font-size:14px;line-height:1.7;color:#0f172a;">'.nl2br(e($testimonial->testimonial)).'</p>'
            .'</div>'
            .'<div style="margin-top:14px;border:1px solid #bfdbfe;border-radius:16px;padding:15px 16px;background:#f8fbff;">'
            .'<p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#0369a1;font-weight:700;">Images</p>'
            .$linksHtml
            .'</div>'
            .'<p style="margin:14px 0 0 0;"><a href="'.e($adminTestimonialsUrl).'" style="display:inline-block;background:linear-gradient(135deg,#0ea5e9,#14b8a6);color:#ecfeff;text-decoration:none;font-weight:700;padding:11px 16px;border-radius:999px;">Open Admin Testimonials</a></p>'
            .'<p style="margin:18px 0 0 0;font-size:14px;color:#0f172a;">With sparkles,<br><strong>Sprinkle Fairydust Face Painting</strong></p>'
            .'</td></tr>'
            .'</table>'
            .'<p style="margin:12px 0 0 0;text-align:center;font-size:12px;color:#64748b;">Creating magical moments, one painted smile at a time.</p>'
            .'</td></tr></table></body></html>';
    }

    private function testimonialNotificationEmailText(Testimonial $testimonial, string $adminTestimonialsUrl): string
    {
        $submittedAt = optional($testimonial->created_at)->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s');

        $lines = [
            'New Testimonial Submission',
            '==========================',
            '',
            'Name: '.$testimonial->name,
            'Submitted At: '.$submittedAt,
            '',
            'Testimonial:',
            $testimonial->testimonial,
            '',
            'Images:',
        ];

        $urls = collect($testimonial->urls ?? [])->filter()->values();

        if ($urls->isEmpty()) {
            $lines[] = '—';
        } else {
            foreach ($urls as $url) {
                $lines[] = '- '.$url;
            }
        }

        $lines[] = '';
        $lines[] = 'Admin Testimonials: '.$adminTestimonialsUrl;

        return implode("\n", $lines);
    }

    private function emailRow(string $label, string $value): string
    {
        return '<tr>'
            .'<td style="padding:8px 0;border-bottom:1px solid #e2e8f0;color:#475569;">'.e($label).'</td>'
            .'<td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;font-weight:700;">'.$value.'</td>'
            .'</tr>';
    }
}
