<?php

use App\Models\PageView;
use App\Models\Quote;
use App\Models\User;

it('returns aggregate tracking stats with quote-linked activity for admin users', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    Quote::query()->create([
        'name' => 'Tracked Client',
        'email' => 'tracked@example.com',
        'anonymous_id' => 'anon_quote_linked',
    ]);

    PageView::query()->create([
        'anonymous_id' => 'anon_other_visitor',
        'page_key' => 'home',
        'path' => '/',
        'event_type' => 'view',
        'duration_seconds' => null,
        'referrer' => 'google.com',
        'country_code' => 'NZ',
        'viewed_at' => now()->subMinutes(30),
    ]);

    PageView::query()->create([
        'anonymous_id' => 'anon_quote_linked',
        'page_key' => 'gallery',
        'path' => '/gallery',
        'event_type' => 'view',
        'duration_seconds' => null,
        'referrer' => 'facebook.com',
        'country_code' => 'AU',
        'viewed_at' => now()->subMinutes(20),
    ]);

    PageView::query()->create([
        'anonymous_id' => 'anon_quote_linked',
        'page_key' => 'designs',
        'path' => '/designs',
        'event_type' => 'view',
        'duration_seconds' => null,
        'referrer' => 'google.com',
        'country_code' => 'AU',
        'viewed_at' => now()->subMinutes(10),
    ]);

    PageView::query()->create([
        'anonymous_id' => 'anon_quote_linked',
        'page_key' => 'designs',
        'path' => '/designs',
        'event_type' => 'engagement',
        'duration_seconds' => 120,
        'country_code' => 'AU',
        'viewed_at' => now()->subMinutes(9),
    ]);

    PageView::query()->create([
        'anonymous_id' => 'anon_quote_linked',
        'page_key' => 'quote_funnel_start',
        'path' => '/quote',
        'event_type' => 'engagement',
        'duration_seconds' => 0,
        'country_code' => 'AU',
        'viewed_at' => now()->subMinutes(8),
    ]);

    PageView::query()->create([
        'anonymous_id' => 'anon_quote_linked',
        'page_key' => 'quote_funnel_package_selected',
        'path' => '/quote',
        'event_type' => 'engagement',
        'duration_seconds' => 0,
        'country_code' => 'AU',
        'viewed_at' => now()->subMinutes(7),
    ]);

    PageView::query()->create([
        'anonymous_id' => 'anon_quote_linked',
        'page_key' => 'quote_funnel_submitted',
        'path' => '/quote',
        'event_type' => 'engagement',
        'duration_seconds' => 0,
        'country_code' => 'AU',
        'viewed_at' => now()->subMinutes(6),
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.tracking.stats'))
        ->assertOk();

    $response->assertJsonPath('overview.total_page_views', 3)
        ->assertJsonPath('overview.unique_visitors', 2)
        ->assertJsonPath('overview.gallery_views', 1)
        ->assertJsonPath('overview.design_views', 1)
        ->assertJsonPath('overview.total_time_seconds', 120)
        ->assertJsonPath('overview.quotes_with_tracking', 1)
        ->assertJsonPath('overview.quote_funnel_starts', 1)
        ->assertJsonPath('overview.quote_funnel_package_selections', 1)
        ->assertJsonPath('overview.quote_funnel_submissions', 1)
        ->assertJsonPath('overview.quote_funnel_conversion_rate', 100)
        ->assertJsonPath('overview.external_referrer_views', 3)
        ->assertJsonPath('overview.unique_external_referrers', 2)
        ->assertJsonPath('external_referrers.0.referrer', 'google.com')
        ->assertJsonPath('external_referrers.0.views', 2)
        ->assertJsonPath('quote_tracking.0.anonymous_id', 'anon_quote_linked')
        ->assertJsonPath('quote_tracking.0.page_views', 2)
        ->assertJsonPath('quote_tracking.0.total_time_seconds', 120);
});

it('forbids non-admin users from viewing tracking stats', function () {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);

    $this->actingAs($user)
        ->getJson(route('admin.tracking.stats'))
        ->assertForbidden();
});
