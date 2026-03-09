<?php

use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TestimonialSeeder;

test('testimonial seeder imports approved testimonials from the project fixture', function () {
    $this->seed(TestimonialSeeder::class);

    expect(Testimonial::query()->count())->toBe(10);

    $testimonial = Testimonial::query()
        ->where('name', 'Sots Saad')
        ->firstOrFail();

    expect($testimonial->testimonial)->toContain('Melody stole the show')
        ->and($testimonial->urls)->toBeNull()
        ->and($testimonial->is_approved)->toBeTrue()
        ->and($testimonial->created_at?->toDateTimeString())->toBe('2025-11-01 04:39:44')
        ->and($testimonial->approved_at?->toDateTimeString())->toBe('2025-11-01 04:39:44');
});

test('database seeder includes the testimonial fixture data', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'brettj@dekode.co.nz')->exists())->toBeTrue()
        ->and(Testimonial::query()->count())->toBe(10)
        ->and(Testimonial::query()->where('name', 'Brooke')->exists())->toBeTrue();
});
