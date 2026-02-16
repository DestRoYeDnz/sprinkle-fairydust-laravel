<?php

use App\Models\Testimonial;

it('stores public testimonials as pending approval', function () {
    $response = $this->postJson('/api/testimonials', [
        'name' => 'Public User',
        'testimonial' => 'Loved the face painting!',
        'urls' => ['https://example.com/photo.jpg'],
    ]);

    $response->assertOk()->assertJsonPath('success', true);
    $response->assertJsonPath('message', 'Thanks! Your testimonial was submitted and is waiting for admin approval.');

    $this->assertDatabaseHas('testimonials', [
        'name' => 'Public User',
        'testimonial' => 'Loved the face painting!',
        'is_approved' => false,
    ]);
});

it('returns only approved testimonials on the public testimonials endpoint', function () {
    $approved = Testimonial::query()->create([
        'name' => 'Approved',
        'testimonial' => 'This should appear publicly.',
        'is_approved' => true,
        'approved_at' => now(),
    ]);

    $pending = Testimonial::query()->create([
        'name' => 'Pending',
        'testimonial' => 'This should stay hidden.',
        'is_approved' => false,
        'approved_at' => null,
    ]);

    $response = $this->getJson('/api/testimonials?limit=20');

    $response->assertOk();
    $ids = collect($response->json('testimonials'))->pluck('id')->all();

    expect($ids)->toContain($approved->id);
    expect($ids)->not->toContain($pending->id);
});
