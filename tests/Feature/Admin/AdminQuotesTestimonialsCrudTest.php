<?php

use App\Mail\StyledHtmlMail;
use App\Models\Quote;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

it('allows admin users to manage quotes', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $createResponse = $this->actingAs($admin)
        ->postJson(route('admin.quotes.store'), [
            'name' => 'Jamie Smith',
            'email' => 'jamie@example.com',
            'phone' => '021 999 1111',
            'guest_count' => 30,
            'package_name' => 'Classic Birthday Magic',
            'services_requested' => ['Face Painting', 'Glitter Tattoos'],
            'travel_area' => 'Papakura',
            'venue_type' => 'outdoor',
            'heard_about' => 'Facebook',
            'notes' => 'Need quick queue designs',
            'terms_accepted' => true,
            'anonymous_id' => 'anon_admin_quote_1',
            'event_type' => 'Birthday',
            'event_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '13:00',
            'address' => '55 Sample Road',
            'calc_payment_type' => 'hourly',
            'calc_base_amount' => 360,
            'calc_setup_amount' => 60,
            'calc_travel_amount' => 25,
            'calc_subtotal' => 445,
            'calc_gst_amount' => 66.75,
            'calc_total_amount' => 511.75,
        ]);

    $createResponse->assertOk()->assertJsonPath('success', true);

    $quoteId = $createResponse->json('quote.id');

    $this->assertDatabaseHas('quotes', [
        'id' => $quoteId,
        'name' => 'Jamie Smith',
        'email' => 'jamie@example.com',
        'phone' => '021 999 1111',
        'guest_count' => 30,
        'package_name' => 'Classic Birthday Magic',
        'anonymous_id' => 'anon_admin_quote_1',
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.quotes.index'))
        ->assertOk()
        ->assertJsonPath('0.id', $quoteId);

    $updateResponse = $this->actingAs($admin)
        ->putJson(route('admin.quotes.update', ['quote' => $quoteId]), [
            'name' => 'Jamie Smith',
            'email' => 'jamie@example.com',
            'phone' => '021 222 3333',
            'guest_count' => 35,
            'package_name' => 'Festival Crowd Package',
            'services_requested' => ['Face Painting'],
            'travel_area' => 'Drury',
            'venue_type' => 'mixed',
            'heard_about' => 'Referral',
            'notes' => 'Updated quote notes',
            'terms_accepted' => true,
            'event_type' => 'Festival',
            'event_date' => now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '14:00',
            'total_hours' => 3,
            'address' => '88 Updated Avenue',
            'calc_payment_type' => 'package',
            'calc_base_amount' => 500,
            'calc_setup_amount' => 0,
            'calc_travel_amount' => 40,
            'calc_subtotal' => 540,
            'calc_gst_amount' => 81,
            'calc_total_amount' => 621,
        ]);

    $updateResponse->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('quotes', [
        'id' => $quoteId,
        'event_type' => 'Festival',
        'phone' => '021 222 3333',
        'guest_count' => 35,
        'package_name' => 'Festival Crowd Package',
        'address' => '88 Updated Avenue',
        'calc_payment_type' => 'package',
        'calc_total_amount' => 621,
    ]);

    config([
        'services.sprinkle.quote_admin_copy_email' => 'admin@sprinkle.test',
        'mail.from.address' => 'quotes@sprinkle.test',
        'app.url' => 'https://sprinkle.test',
    ]);

    Mail::fake();

    $this->actingAs($admin)
        ->postJson(route('admin.quotes.decline', ['quote' => $quoteId]), [
            'reason' => 'Requested start time is unavailable.',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('quote.artist_decline_reason', 'Requested start time is unavailable.');

    $declinedQuote = Quote::query()->findOrFail($quoteId);
    expect($declinedQuote->artist_declined_at)->not->toBeNull();
    expect($declinedQuote->artist_decline_reason)->toBe('Requested start time is unavailable.');

    Mail::assertSent(StyledHtmlMail::class, function (StyledHtmlMail $mail): bool {
        return $mail->hasTo('jamie@example.com')
            && str_contains($mail->mailSubject, 'Quote Time Update')
            && str_contains($mail->htmlContent, 'Requested start time is unavailable')
            && str_contains($mail->htmlContent, 'Suggest a Different Time')
            && str_contains($mail->htmlContent, '/quotes/');
    });

    $this->actingAs($admin)
        ->postJson(route('admin.quotes.send-email', ['quote' => $quoteId]))
        ->assertOk()
        ->assertJsonPath('success', true);

    Mail::assertSent(StyledHtmlMail::class, function (StyledHtmlMail $mail) {
        return $mail->hasTo('jamie@example.com')
            && $mail->hasBcc('admin@sprinkle.test')
            && str_contains($mail->mailSubject, 'Sprinkle Fairydust Quote')
            && str_contains($mail->htmlContent, 'Sprinkle Fairydust Quote')
            && str_contains($mail->htmlContent, 'Guest Count')
            && str_contains($mail->htmlContent, 'Package');
    });

    $this->assertDatabaseHas('quotes', [
        'id' => $quoteId,
        'email_send_status' => 'sent',
    ]);

    $sentQuote = Quote::query()->findOrFail($quoteId);
    expect($sentQuote->email_send_attempted_at)->not->toBeNull();
    expect($sentQuote->email_send_response)->toBeArray();
    expect(data_get($sentQuote->email_send_response, 'ok'))->toBeTrue();
    expect(data_get($sentQuote->email_send_response, 'mailer'))->toBe(config('mail.default'));

    $this->actingAs($admin)
        ->deleteJson(route('admin.quotes.destroy', ['quote' => $quoteId]))
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('quotes', [
        'id' => $quoteId,
    ]);
});

it('includes add-ons and omits gst when gst is not selected', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    config([
        'services.sprinkle.quote_admin_copy_email' => 'admin@sprinkle.test',
        'mail.from.address' => 'quotes@sprinkle.test',
        'app.url' => 'https://sprinkle.test',
    ]);

    Mail::fake();

    $createResponse = $this->actingAs($admin)
        ->postJson(route('admin.quotes.store'), [
            'name' => 'Add On Client',
            'email' => 'addons@example.com',
            'event_type' => 'Birthday',
            'event_date' => now()->toDateString(),
            'start_time' => '12:00',
            'end_time' => '15:00',
            'package_name' => 'Classic Birthday Magic',
            'services_requested' => [
                'Face Painting',
                'Add-on: Premium Glitter Bar ($65.00)',
                'Add-on: Extra Artist ($120.00)',
            ],
            'calc_payment_type' => 'package',
            'calc_base_amount' => 545,
            'calc_setup_amount' => 0,
            'calc_travel_amount' => 20,
            'calc_subtotal' => 565,
            'calc_gst_amount' => null,
            'calc_total_amount' => 565,
        ]);

    $createResponse->assertOk()->assertJsonPath('success', true);

    $quoteId = $createResponse->json('quote.id');

    $this->assertDatabaseHas('quotes', [
        'id' => $quoteId,
        'calc_gst_amount' => null,
    ]);

    $this->actingAs($admin)
        ->postJson(route('admin.quotes.send-email', ['quote' => $quoteId]))
        ->assertOk()
        ->assertJsonPath('success', true);

    Mail::assertSent(StyledHtmlMail::class, function (StyledHtmlMail $mail): bool {
        return $mail->hasTo('addons@example.com')
            && str_contains($mail->htmlContent, 'Add-ons')
            && str_contains($mail->htmlContent, 'Premium Glitter Bar')
            && str_contains($mail->htmlContent, 'Extra Artist')
            && str_contains($mail->htmlContent, '$185.00')
            && ! str_contains($mail->htmlContent, '>GST<')
            && ! str_contains((string) $mail->textContent, 'GST:');
    });
});

it('forbids non-admin users from managing quotes', function () {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);

    $quote = Quote::query()->create([
        'name' => 'Blocked User',
        'email' => 'blocked@example.com',
    ]);

    $this->actingAs($user)
        ->getJson(route('admin.quotes.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson(route('admin.quotes.store'), [
            'name' => 'No Access',
            'email' => 'no-access@example.com',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->putJson(route('admin.quotes.update', ['quote' => $quote->id]), [
            'name' => 'No Access',
            'email' => 'no-access@example.com',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->deleteJson(route('admin.quotes.destroy', ['quote' => $quote->id]))
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson(route('admin.quotes.send-email', ['quote' => $quote->id]))
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson(route('admin.quotes.decline', ['quote' => $quote->id]), [
            'reason' => 'Unavailable',
        ])
        ->assertForbidden();
});

it('confirms quote via signed email webhook link', function () {
    Mail::fake();

    config([
        'services.sprinkle.quote_confirmed_notification_email' => 'confirmations@sprinkle.test',
    ]);

    $quote = Quote::query()->create([
        'name' => 'Signed Client',
        'email' => 'signed-client@example.com',
        'event_type' => 'Birthday Party',
    ]);

    $url = URL::temporarySignedRoute('quotes.confirm', now()->addMinutes(30), [
        'quote' => $quote->id,
    ]);

    $this->get($url)
        ->assertOk()
        ->assertSee('Quote Confirmation');

    $quote->refresh();
    expect($quote->client_confirmed_at)->not->toBeNull();

    Mail::assertSent(StyledHtmlMail::class, function (StyledHtmlMail $mail): bool {
        return $mail->hasTo('confirmations@sprinkle.test')
            && str_contains($mail->mailSubject, 'Quote Confirmed')
            && str_contains($mail->htmlContent, 'Signed Client');
    });

    Mail::assertSent(StyledHtmlMail::class, function (StyledHtmlMail $mail): bool {
        return $mail->hasTo('signed-client@example.com')
            && str_contains($mail->mailSubject, 'Your Quote is Confirmed')
            && str_contains($mail->htmlContent, 'Thanks for confirming your quote');
    });

    $this->get($url)->assertOk();
    Mail::assertSentTimes(StyledHtmlMail::class, 2);
});

it('tracks quote email opens via signed pixel webhook link', function () {
    $quote = Quote::query()->create([
        'name' => 'Open Tracker',
        'email' => 'open-tracker@example.com',
    ]);

    $url = URL::temporarySignedRoute('quotes.open', now()->addMinutes(30), [
        'quote' => $quote->id,
    ]);

    $this->get($url)
        ->assertOk()
        ->assertHeader('Content-Type', 'image/gif');

    $quote->refresh();
    expect($quote->email_opened_at)->not->toBeNull();
    expect($quote->email_last_opened_at)->not->toBeNull();
    expect($quote->email_open_count)->toBe(1);

    $this->get($url)->assertOk();
    $quote->refresh();
    expect($quote->email_open_count)->toBe(2);
});

it('accepts a signed suggested-time submission and sends required emails', function () {
    Mail::fake();

    config([
        'app.url' => 'https://sprinkle.test',
        'services.sprinkle.quote_reschedule_notification_email' => 'reschedule@sprinkle.test',
        'mail.from.address' => 'quotes@sprinkle.test',
    ]);

    $quote = Quote::query()->create([
        'name' => 'Suggested Time Client',
        'email' => 'suggested-time@example.com',
        'event_type' => 'Birthday Party',
        'event_date' => now()->toDateString(),
        'start_time' => '10:00',
        'end_time' => '12:00',
        'artist_declined_at' => now(),
        'artist_decline_reason' => 'Original time is unavailable.',
    ]);

    $suggestUrl = URL::temporarySignedRoute('quotes.suggest-time', now()->addMinutes(30), [
        'quote' => $quote->id,
    ]);

    $this->get($suggestUrl)
        ->assertOk()
        ->assertSee('Suggest a Different Time');

    $submitUrl = URL::temporarySignedRoute('quotes.suggest-time.submit', now()->addMinutes(30), [
        'quote' => $quote->id,
    ]);

    $this->post($submitUrl, [
        'event_date' => now()->addDays(7)->toDateString(),
        'start_time' => '11:00',
        'end_time' => '13:00',
        'notes' => 'Afternoon slot would work better for us.',
    ])
        ->assertOk()
        ->assertSee('Thanks! We Received Your New Time');

    $quote->refresh();
    expect($quote->client_suggested_time_at)->not->toBeNull();
    expect(optional($quote->client_suggested_event_date)->format('Y-m-d'))->toBe(now()->addDays(7)->toDateString());
    expect((string) $quote->client_suggested_start_time)->toStartWith('11:00');
    expect((string) $quote->client_suggested_end_time)->toStartWith('13:00');
    expect($quote->client_suggested_time_notes)->toBe('Afternoon slot would work better for us.');

    Mail::assertSent(StyledHtmlMail::class, function (StyledHtmlMail $mail): bool {
        return $mail->hasTo('reschedule@sprinkle.test')
            && str_contains($mail->mailSubject, 'New Time Suggested')
            && str_contains($mail->htmlContent, 'Suggested Time Client')
            && str_contains($mail->htmlContent, 'Open Admin Quotes')
            && str_contains($mail->htmlContent, 'Sprinkle Fairydust Face Painting');
    });

    Mail::assertSent(StyledHtmlMail::class, function (StyledHtmlMail $mail): bool {
        return $mail->hasTo('suggested-time@example.com')
            && str_contains($mail->mailSubject, 'We Received Your New Time')
            && str_contains($mail->htmlContent, 'New Time Suggestion Received')
            && str_contains($mail->htmlContent, 'Sprinkle Fairydust Face Painting');
    });
});

it('prevents declining a quote that has already been confirmed', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $quote = Quote::query()->create([
        'name' => 'Already Confirmed',
        'email' => 'already-confirmed@example.com',
        'client_confirmed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->postJson(route('admin.quotes.decline', ['quote' => $quote->id]), [
            'reason' => 'Requested time no longer available.',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Confirmed quotes cannot be declined.');

    $quote->refresh();
    expect($quote->artist_declined_at)->toBeNull();
    expect($quote->artist_decline_reason)->toBeNull();
});

it('rejects unsigned quote webhook links', function () {
    $quote = Quote::query()->create([
        'name' => 'Unsigned Client',
        'email' => 'unsigned-client@example.com',
    ]);

    $this->get("/quotes/{$quote->id}/confirm")->assertForbidden();
    $this->get("/quotes/{$quote->id}/open")->assertForbidden();
    $this->get("/quotes/{$quote->id}/suggest-time")->assertForbidden();
    $this->post("/quotes/{$quote->id}/suggest-time", [
        'event_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00',
        'end_time' => '11:00',
    ])->assertForbidden();
});

it('allows admin users to manage testimonials', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $createResponse = $this->actingAs($admin)
        ->postJson(route('admin.testimonials.store'), [
            'name' => 'Sam',
            'testimonial' => 'Amazing work and very friendly!',
            'urls' => [
                'https://example.com/one.jpg',
                'https://example.com/two.jpg',
            ],
            'is_approved' => false,
        ]);

    $createResponse->assertOk()->assertJsonPath('success', true);

    $testimonialId = $createResponse->json('testimonial.id');

    $this->assertDatabaseHas('testimonials', [
        'id' => $testimonialId,
        'name' => 'Sam',
        'is_approved' => false,
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.testimonials.index'))
        ->assertOk()
        ->assertJsonPath('0.id', $testimonialId);

    $updateResponse = $this->actingAs($admin)
        ->putJson(route('admin.testimonials.update', ['testimonial' => $testimonialId]), [
            'name' => 'Sam Updated',
            'testimonial' => 'Updated testimonial text',
            'urls' => ['https://example.com/updated.jpg'],
            'is_approved' => true,
        ]);

    $updateResponse->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('testimonials', [
        'id' => $testimonialId,
        'name' => 'Sam Updated',
        'testimonial' => 'Updated testimonial text',
        'is_approved' => true,
    ]);

    $this->actingAs($admin)
        ->deleteJson(route('admin.testimonials.destroy', ['testimonial' => $testimonialId]))
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('testimonials', [
        'id' => $testimonialId,
    ]);
});

it('forbids non-admin users from managing testimonials', function () {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);

    $testimonial = Testimonial::query()->create([
        'name' => 'Blocked',
        'testimonial' => 'Should not be editable by non-admin',
    ]);

    $this->actingAs($user)
        ->getJson(route('admin.testimonials.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson(route('admin.testimonials.store'), [
            'name' => 'No Access',
            'testimonial' => 'No access testimonial',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->putJson(route('admin.testimonials.update', ['testimonial' => $testimonial->id]), [
            'name' => 'No Access',
            'testimonial' => 'No access testimonial',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->deleteJson(route('admin.testimonials.destroy', ['testimonial' => $testimonial->id]))
        ->assertForbidden();
});
