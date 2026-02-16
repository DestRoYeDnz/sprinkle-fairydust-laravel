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
        'anonymous_id' => ' anon_quote$123 ',
        'event' => 'Birthday',
        'date' => now()->toDateString(),
        'address' => '12 Sparkle Lane',
        'details' => 'Please focus on glitter designs',
    ])->assertOk()
        ->assertJsonPath('success', true);

    $quote = Quote::query()->firstOrFail();

    expect($quote->anonymous_id)->toBe('anon_quote123');

    Mail::assertSent(StyledHtmlMail::class, function (StyledHtmlMail $mail): bool {
        return $mail->hasTo('admin@sprinkle.test');
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
    ])->assertOk()
        ->assertJsonPath('success', true);

    $quote = Quote::query()->firstOrFail();

    expect($quote->anonymous_id)->toBeNull();

    Mail::assertSent(StyledHtmlMail::class, function (StyledHtmlMail $mail): bool {
        return $mail->hasTo('brettj@dekode.co.nz');
    });
});
