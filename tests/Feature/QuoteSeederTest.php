<?php

use App\Models\Quote;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\QuoteSeeder;

test('quote seeder creates five initial-form quotes without calculated or email values', function () {
    $this->seed(QuoteSeeder::class);

    expect(Quote::query()->count())->toBe(5);

    $quote = Quote::query()
        ->where('email', 'aroha.thompson@example.test')
        ->firstOrFail();

    expect($quote->name)->toBe('Aroha Thompson')
        ->and($quote->phone)->toBe('021 456 102')
        ->and($quote->guest_count)->toBe(20)
        ->and($quote->package_name)->toBe('Mini Party Sparkle')
        ->and($quote->services_requested)->toBe(['Face Painting'])
        ->and($quote->travel_area)->toBe('Auckland Central')
        ->and($quote->venue_type)->toBe('outdoor')
        ->and($quote->heard_about)->toBe('Google search')
        ->and($quote->notes)->toContain('Birthday party')
        ->and($quote->terms_accepted)->toBeTrue()
        ->and($quote->event_type)->toBe('Birthday Party')
        ->and($quote->event_date?->toDateString())->not->toBeNull()
        ->and($quote->address)->toBe('25 Seaview Road, Mission Bay, Auckland')
        ->and($quote->start_time)->toBe('10:00:00')
        ->and($quote->end_time)->toBe('12:00:00')
        ->and($quote->anonymous_id)->toBeNull()
        ->and($quote->total_hours)->toBeNull()
        ->and($quote->calc_payment_type)->toBeNull()
        ->and($quote->calc_base_amount)->toBeNull()
        ->and($quote->calc_setup_amount)->toBeNull()
        ->and($quote->calc_travel_amount)->toBeNull()
        ->and($quote->calc_subtotal)->toBeNull()
        ->and($quote->calc_gst_amount)->toBeNull()
        ->and($quote->calc_total_amount)->toBeNull()
        ->and($quote->email_send_status)->toBeNull()
        ->and($quote->email_send_attempted_at)->toBeNull()
        ->and($quote->email_send_response)->toBeNull()
        ->and($quote->client_confirmed_at)->toBeNull()
        ->and($quote->artist_declined_at)->toBeNull()
        ->and($quote->artist_decline_reason)->toBeNull()
        ->and($quote->client_suggested_time_at)->toBeNull()
        ->and($quote->client_suggested_event_date)->toBeNull()
        ->and($quote->client_suggested_start_time)->toBeNull()
        ->and($quote->client_suggested_end_time)->toBeNull()
        ->and($quote->client_suggested_time_notes)->toBeNull()
        ->and($quote->email_opened_at)->toBeNull()
        ->and($quote->email_last_opened_at)->toBeNull()
        ->and($quote->email_open_count)->toBe(0);
});

test('database seeder includes admin user and quote seeder data', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'brettj@dekode.co.nz')->exists())->toBeTrue()
        ->and(Quote::query()->count())->toBe(5);
});
