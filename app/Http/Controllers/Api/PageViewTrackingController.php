<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class PageViewTrackingController extends Controller
{
    /**
     * Record an anonymous page view.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'anonymous_id' => ['required', 'string', 'max:80'],
            'page_key' => ['required', 'string', 'max:80'],
            'path' => ['required', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:512'],
            'event_type' => ['nullable', Rule::in(['view', 'engagement'])],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ]);

        $eventType = $validated['event_type'] ?? 'view';
        $durationSeconds = $eventType === 'engagement'
            ? ($validated['duration_seconds'] ?? 0)
            : null;
        $normalizedPath = $this->normalizePath($validated['path']);

        if ($this->isAdminPath($normalizedPath)) {
            return response()->json([
                'success' => true,
                'tracked' => false,
            ], 202);
        }

        PageView::query()->create([
            'anonymous_id' => $this->sanitizeAnonymousId($validated['anonymous_id']),
            'page_key' => $validated['page_key'],
            'path' => $normalizedPath,
            'event_type' => $eventType,
            'duration_seconds' => $durationSeconds,
            'referrer' => $validated['referrer'] ?? null,
            'country_code' => $this->resolveCountryCode($request),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'viewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
        ], 201);
    }

    private function sanitizeAnonymousId(string $anonymousId): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9\-_]/', '', trim($anonymousId)) ?? '';

        if ($sanitized === '') {
            return 'anon-'.bin2hex(random_bytes(8));
        }

        return substr($sanitized, 0, 80);
    }

    private function resolveCountryCode(Request $request): string
    {
        $countryFromHeader = $this->resolveCountryCodeFromHeaders($request);

        if ($countryFromHeader !== null) {
            return $countryFromHeader;
        }

        $ipAddress = (string) $request->ip();

        if (! $this->isPublicIpAddress($ipAddress)) {
            return 'UNKNOWN';
        }

        $cacheMinutes = max(5, (int) config('services.sprinkle.geoip_cache_minutes', 1440));
        $cacheKey = 'tracking:geoip:country:'.hash('sha256', $ipAddress);

        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($ipAddress): string {
            $countryCode = $this->resolveCountryCodeFromGeoIpExtension($ipAddress)
                ?? $this->resolveCountryCodeFromHttpGeoIp($ipAddress);

            return $countryCode ?? 'UNKNOWN';
        });
    }

    private function resolveCountryCodeFromHeaders(Request $request): ?string
    {
        $candidates = [
            $request->header('CF-IPCountry'),
            $request->header('CloudFront-Viewer-Country'),
            $request->header('X-Vercel-IP-Country'),
            $request->header('Fly-Client-Country'),
            $request->header('Fastly-GeoIP-Country-Code'),
            $request->header('X-Country-Code'),
            $request->header('X-Appengine-Country'),
        ];

        foreach ($candidates as $candidate) {
            $value = strtoupper(trim((string) $candidate));

            if ($value === '' || $value === 'XX' || $value === 'T1') {
                continue;
            }

            if ($this->isValidIsoCountryCode($value)) {
                return $value;
            }
        }

        return null;
    }

    private function resolveCountryCodeFromGeoIpExtension(string $ipAddress): ?string
    {
        if (! function_exists('geoip_country_code_by_name')) {
            return null;
        }

        try {
            $countryCode = strtoupper(trim((string) geoip_country_code_by_name($ipAddress)));
        } catch (\Throwable) {
            return null;
        }

        return $this->isValidIsoCountryCode($countryCode) ? $countryCode : null;
    }

    private function resolveCountryCodeFromHttpGeoIp(string $ipAddress): ?string
    {
        $geoIpEndpoint = trim((string) config('services.sprinkle.geoip_endpoint'));

        if ($geoIpEndpoint === '') {
            return null;
        }

        $timeoutSeconds = max(1.0, (float) config('services.sprinkle.geoip_timeout_seconds', 2.0));
        $token = trim((string) config('services.sprinkle.geoip_token'));

        try {
            $request = Http::timeout($timeoutSeconds)->acceptJson();

            if ($token !== '') {
                $request = $request->withToken($token);
            }

            if (str_contains($geoIpEndpoint, '{ip}')) {
                $response = $request->get(str_replace('{ip}', urlencode($ipAddress), $geoIpEndpoint));
            } else {
                $response = $request->get($geoIpEndpoint, ['ip' => $ipAddress]);
            }

            if (! $response->successful()) {
                return null;
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                return null;
            }

            $candidates = [
                data_get($payload, 'country_code'),
                data_get($payload, 'countryCode'),
                data_get($payload, 'country'),
                data_get($payload, 'country_iso_code'),
                data_get($payload, 'data.country_code'),
                data_get($payload, 'data.countryCode'),
            ];

            foreach ($candidates as $candidate) {
                $value = strtoupper(trim((string) $candidate));

                if ($this->isValidIsoCountryCode($value)) {
                    return $value;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function isPublicIpAddress(string $ipAddress): bool
    {
        if ($ipAddress === '') {
            return false;
        }

        return filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    private function isValidIsoCountryCode(string $value): bool
    {
        return preg_match('/^[A-Z]{2}$/', $value) === 1;
    }

    private function normalizePath(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed === '' || $trimmed === '/') {
            return '/';
        }

        $normalized = str_starts_with($trimmed, '/') ? $trimmed : '/'.$trimmed;

        return rtrim($normalized, '/');
    }

    private function isAdminPath(string $path): bool
    {
        return str_starts_with($path, '/admin');
    }
}
