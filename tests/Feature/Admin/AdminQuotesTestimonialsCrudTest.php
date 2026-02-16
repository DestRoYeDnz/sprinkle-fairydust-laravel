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
            'event_type' => 'Festival',
            'event_date' => now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '14:00',
            'total_hours' => 3,
            'address' => '88 Updated Avenue',
            'calc_payment_type' => 'perface',
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
        'address' => '88 Updated Avenue',
        'calc_payment_type' => 'perface',
        'calc_total_amount' => 621,
    ]);

    config([
        'services.sprinkle.quote_admin_copy_email' => 'admin@sprinkle.test',
        'mail.from.address' => 'quotes@sprinkle.test',
        'app.url' => 'https://sprinkle.test',
    ]);

    Mail::fake();

    $this->actingAs($admin)
        ->postJson(route('admin.quotes.send-email', ['quote' => $quoteId]))
        ->assertOk()
        ->assertJsonPath('success', true);

    Mail::assertSent(StyledHtmlMail::class, function (StyledHtmlMail $mail) {
        return $mail->hasTo('jamie@example.com')
            && $mail->hasBcc('admin@sprinkle.test')
            && str_contains($mail->mailSubject, 'Sprinkle Fairydust Quote')
            && str_contains($mail->htmlContent, 'Sprinkle Fairydust Quote');
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
});

it('confirms quote via signed email webhook link', function () {
    $quote = Quote::query()->create([
        'name' => 'Signed Client',
        'email' => 'signed-client@example.com',
    ]);

    $url = URL::temporarySignedRoute('quotes.confirm', now()->addMinutes(30), [
        'quote' => $quote->id,
    ]);

    $this->get($url)
        ->assertOk()
        ->assertSee('Quote Confirmation');

    $quote->refresh();
    expect($quote->client_confirmed_at)->not->toBeNull();
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

it('rejects unsigned quote webhook links', function () {
    $quote = Quote::query()->create([
        'name' => 'Unsigned Client',
        'email' => 'unsigned-client@example.com',
    ]);

    $this->get("/quotes/{$quote->id}/confirm")->assertForbidden();
    $this->get("/quotes/{$quote->id}/open")->assertForbidden();
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
