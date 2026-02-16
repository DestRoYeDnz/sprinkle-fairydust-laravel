<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;

class TrackingStatsController extends Controller
{
    /**
     * Return aggregate anonymous tracking stats for admin.
     */
    public function index(): JsonResponse
    {
        $viewEvents = PageView::query()->where('event_type', 'view');
        $engagementEvents = PageView::query()->where('event_type', 'engagement');

        $totalPageViews = (clone $viewEvents)->count();
        $uniqueVisitors = (clone $viewEvents)
            ->distinct('anonymous_id')
            ->count('anonymous_id');
        $galleryViews = (clone $viewEvents)
            ->where('page_key', 'gallery')
            ->count();
        $designViews = (clone $viewEvents)
            ->where('page_key', 'designs')
            ->count();
        $totalTimeSeconds = (int) (clone $engagementEvents)->sum('duration_seconds');
        $averageTimePerVisitorSeconds = $uniqueVisitors > 0
            ? (int) round($totalTimeSeconds / $uniqueVisitors)
            : 0;

        $countryViews = (clone $viewEvents)
            ->selectRaw("COALESCE(NULLIF(country_code, ''), 'UNKNOWN') as country_code, COUNT(*) as views")
            ->groupBy('country_code')
            ->orderByDesc('views')
            ->limit(20)
            ->get();

        $pageViews = (clone $viewEvents)
            ->selectRaw('page_key, COUNT(*) as views')
            ->groupBy('page_key')
            ->orderByDesc('views')
            ->limit(20)
            ->get();

        $viewsLast24Hours = (clone $viewEvents)
            ->where('viewed_at', '>=', now()->subDay())
            ->count();

        $viewsLast7Days = (clone $viewEvents)
            ->where('viewed_at', '>=', now()->subDays(7))
            ->count();

        $viewsLast30Days = (clone $viewEvents)
            ->where('viewed_at', '>=', now()->subDays(30))
            ->count();

        $dailyViews = (clone $viewEvents)
            ->selectRaw('DATE(viewed_at) as viewed_date, COUNT(*) as views')
            ->where('viewed_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('viewed_date')
            ->orderBy('viewed_date')
            ->get();

        $trackingByAnonymousId = PageView::query()
            ->selectRaw('anonymous_id')
            ->selectRaw('SUM(CASE WHEN event_type = "view" THEN 1 ELSE 0 END) as page_views')
            ->selectRaw('SUM(CASE WHEN event_type = "view" AND page_key = "gallery" THEN 1 ELSE 0 END) as gallery_views')
            ->selectRaw('SUM(CASE WHEN event_type = "view" AND page_key = "designs" THEN 1 ELSE 0 END) as design_views')
            ->selectRaw('SUM(CASE WHEN event_type = "engagement" THEN COALESCE(duration_seconds, 0) ELSE 0 END) as total_time_seconds')
            ->selectRaw('MIN(viewed_at) as first_viewed_at')
            ->selectRaw('MAX(viewed_at) as last_viewed_at')
            ->whereNotNull('anonymous_id')
            ->where('anonymous_id', '!=', '')
            ->groupBy('anonymous_id')
            ->get()
            ->keyBy('anonymous_id');

        $quoteTracking = Quote::query()
            ->select([
                'id',
                'name',
                'email',
                'anonymous_id',
                'created_at',
            ])
            ->whereNotNull('anonymous_id')
            ->where('anonymous_id', '!=', '')
            ->latest('created_at')
            ->limit(30)
            ->get()
            ->map(function (Quote $quote) use ($trackingByAnonymousId): array {
                $tracking = $trackingByAnonymousId->get($quote->anonymous_id);

                return [
                    'quote_id' => $quote->id,
                    'name' => $quote->name,
                    'email' => $quote->email,
                    'anonymous_id' => $quote->anonymous_id,
                    'page_views' => (int) ($tracking?->page_views ?? 0),
                    'gallery_views' => (int) ($tracking?->gallery_views ?? 0),
                    'design_views' => (int) ($tracking?->design_views ?? 0),
                    'total_time_seconds' => (int) ($tracking?->total_time_seconds ?? 0),
                    'first_viewed_at' => $tracking?->first_viewed_at ?? null,
                    'last_viewed_at' => $tracking?->last_viewed_at ?? null,
                    'quote_created_at' => $quote->created_at,
                ];
            })
            ->values();

        $quotesWithTracking = $quoteTracking
            ->filter(fn (array $item): bool => $item['page_views'] > 0)
            ->count();

        return response()->json([
            'overview' => [
                'total_page_views' => $totalPageViews,
                'unique_visitors' => $uniqueVisitors,
                'gallery_views' => $galleryViews,
                'design_views' => $designViews,
                'views_last_24h' => $viewsLast24Hours,
                'views_last_7d' => $viewsLast7Days,
                'views_last_30d' => $viewsLast30Days,
                'total_time_seconds' => $totalTimeSeconds,
                'average_time_per_visitor_seconds' => $averageTimePerVisitorSeconds,
                'quotes_with_tracking' => $quotesWithTracking,
            ],
            'country_views' => $countryViews,
            'page_views' => $pageViews,
            'daily_views' => $dailyViews,
            'quote_tracking' => $quoteTracking,
        ]);
    }
}
