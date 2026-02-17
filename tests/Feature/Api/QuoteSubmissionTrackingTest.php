<?php

use App\Mail\StyledHtmlMail;
use App\Models\Quote;
use Illuminate\Support\Facades\Mail;

it('stores the anonymous tracking id when submitting a quote request', function () {
    config([
        'services.sprinkle.quote_notification_email' => 'admin@sprinkle.test',
        'mail.from.address' => 'quotes@sprinkle.test',
    ]);

    Mail::fake();

    $this->postJson('/api/quotes', [
        'name' => 'Taylor Visitor',
        'email' => 'taylor@example.com',
        'phone' => '+64 21 111 2222',
        'guest_count' => 24,
        'anonymous_id' => ' anon_quote$123 ',
        'event' => 'Birthday',
        'date' => now()->toDateString(),
        'package_name' => 'Classic Birthday Magic',
        'services_requested' => ['Face Painting', 'Glitter Tattoos'],
        'travel_area' => 'Pukekohe',
        'venue_type' => 'indoor',
        'heard_about' => 'Google search',
        'address' => '12 Sparkle Lane',
        'details' => 'Please focus on glitter designs',
        'terms_accepted' => true,
    ])->assertOk()
        ->assertJsonPath('success', true);

    $quote = Quote::query()->firstOrFail();

    expect($quote->anonymous_id)->toBe('anon_quote123')
        ->and($quote->phone)->toBe('+64 21 111 2222')
        ->and($quote->guest_count)->toBe(24)
        ->and($quote->package_name)->toBe('Classic Birthday Magic')
        ->and($quote->services_requested)->toBe(['Face Painting', 'Glitter Tattoos'])
        ->and($quote->terms_accepted)->toBeTrue()
        ->and($quote->terms_accepted_at)->not->toBeNull();

    Mail::assertSent(StyledHtmlMail::class, function (StyledHtmlMail $mail): bool {
        return $mail->hasTo('admin@sprinkle.test')
            && str_contains($mail->htmlContent, 'Sprinkle Fairydust Face Painting')
            && str_contains($mail->htmlContent, 'Open Quote Calculator');
    });
});

it('allows quote submissions without an anonymous tracking id', function () {
    config([
        'services.sprinkle.quote_notification_email' => '',
    ]);

    Mail::fake();

    $this->postJson('/api/quotes', [
        'name' => 'No Tracking ID',
        'email' => 'no-id@example.com',
        'event' => 'Community Event',
        'date' => now()->toDateString(),
        'terms_accepted' => true,
    ])->assertOk()
        ->assertJsonPath('success', true);

    $quote = Quote::query()->firstOrFail();

    expect($quote->anonymous_id)->toBeNull();

    Mail::assertSent(StyledHtmlMail::class, function (StyledHtmlMail $mail): bool {
        return $mail->hasTo('brettj@dekode.co.nz')
            && str_contains($mail->htmlContent, 'Sprinkle Fairydust Face Painting')
            && str_contains($mail->htmlContent, 'Open Quote Calculator');
    });
});

it('stores larger combined services and add-ons lists for quote submissions', function () {
    config([
        'services.sprinkle.quote_notification_email' => 'admin@sprinkle.test',
    ]);

    Mail::fake();

    $servicesRequested = collect(range(1, 12))
        ->map(fn (int $index): string => "Add-on: Option {$index}")
        ->all();

    $this->postJson('/api/quotes', [
        'name' => 'Addon Client',
        'email' => 'addon-client@example.com',
        'event' => 'Community Day',
        'date' => now()->toDateString(),
        'package_name' => 'Festival Crowd Package',
        'services_requested' => $servicesRequested,
        'terms_accepted' => true,
    ])->assertOk()
        ->assertJsonPath('success', true);

    $quote = Quote::query()->firstOrFail();

    expect($quote->services_requested)->toBe($servicesRequested);
});
