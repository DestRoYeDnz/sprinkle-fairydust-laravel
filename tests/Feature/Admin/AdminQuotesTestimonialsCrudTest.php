<?php

use App\Models\Quote;
use App\Models\Testimonial;
use App\Models\User;

it('allows admin users to manage quotes', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $createResponse = $this->actingAs($admin)
        ->postJson(route('admin.quotes.store'), [
            'name' => 'Jamie Smith',
            'email' => 'jamie@example.com',
            'event_type' => 'Birthday',
            'event_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '13:00',
            'address' => '55 Sample Road',
            'details' => 'Outdoor setup requested',
        ]);

    $createResponse->assertOk()->assertJsonPath('success', true);

    $quoteId = $createResponse->json('quote.id');

    $this->assertDatabaseHas('quotes', [
        'id' => $quoteId,
        'name' => 'Jamie Smith',
        'email' => 'jamie@example.com',
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
            'details' => 'Updated details',
        ]);

    $updateResponse->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('quotes', [
        'id' => $quoteId,
        'event_type' => 'Festival',
        'address' => '88 Updated Avenue',
        'details' => 'Updated details',
    ]);

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
