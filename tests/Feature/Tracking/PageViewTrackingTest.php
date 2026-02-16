<?php

use App\Models\PageView;
use Illuminate\Support\Facades\Http;

it('records anonymous page view events with country and sanitized IDs', function () {
    $this->postJson('/api/tracking/page-views', [
        'anonymous_id' => ' anon-ABC$123 ',
        'page_key' => 'gallery',
        'path' => '/gallery',
        'referrer' => 'https://example.com/start',
    ], [
        'CF-IPCountry' => 'US',
    ])->assertCreated()
        ->assertJsonPath('success', true);

    $pageView = PageView::query()->firstOrFail();

    expect($pageView->anonymous_id)->toBe('anon-ABC123')
        ->and($pageView->event_type)->toBe('view')
        ->and($pageView->duration_seconds)->toBeNull()
        ->and($pageView->country_code)->toBe('US')
        ->and($pageView->page_key)->toBe('gallery');
});

it('records engagement duration events', function () {
    $this->postJson('/api/tracking/page-views', [
        'anonymous_id' => 'anon_time_visitor',
        'page_key' => 'designs',
        'path' => '/designs',
        'referrer' => null,
        'event_type' => 'engagement',
        'duration_seconds' => 87,
    ])->assertCreated()
        ->assertJsonPath('success', true);

    $pageView = PageView::query()->firstOrFail();

    expect($pageView->event_type)->toBe('engagement')
        ->and($pageView->duration_seconds)->toBe(87);
});

it('uses configured geoip http fallback for public ip addresses when headers are missing', function () {
    config([
        'services.sprinkle.geoip_endpoint' => 'https://geo.example.test/lookup',
        'services.sprinkle.geoip_token' => '',
        'services.sprinkle.geoip_timeout_seconds' => 2,
        'services.sprinkle.geoip_cache_minutes' => 10,
    ]);

    Http::fake([
        'https://geo.example.test/lookup*' => Http::response([
            'country_code' => 'NZ',
        ], 200),
    ]);

    $this->withServerVariables([
        'REMOTE_ADDR' => '8.8.8.8',
    ])->postJson('/api/tracking/page-views', [
        'anonymous_id' => 'anon_geo_lookup',
        'page_key' => 'home',
        'path' => '/',
        'referrer' => null,
    ])->assertCreated()
        ->assertJsonPath('success', true);

    $pageView = PageView::query()->firstOrFail();

    expect($pageView->country_code)->toBe('NZ');

    Http::assertSentCount(1);
});

it('does not persist admin page tracking events', function () {
    $this->postJson('/api/tracking/page-views', [
        'anonymous_id' => 'anon_admin_skip',
        'page_key' => 'admin_tracking',
        'path' => '/admin/tracking',
        'referrer' => '/admin',
    ])->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('tracked', false);

    expect(PageView::query()->count())->toBe(0);
});

it('validates required tracking payload fields', function () {
    $this->postJson('/api/tracking/page-views', [
        'anonymous_id' => 'anon_missing_path',
        'page_key' => 'home',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['path']);
});
