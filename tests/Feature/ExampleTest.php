<?php

use App\Models\Testimonial;
use Inertia\Testing\AssertableInertia as Assert;

test('home page renders approved testimonials and updated what we do cards', function () {
    Testimonial::query()->create([
        'name' => 'Older Client',
        'testimonial' => 'Melody made the party feel calm, fun, and beautifully organised.',
        'is_approved' => true,
        'approved_at' => now()->subDay(),
    ]);

    Testimonial::query()->create([
        'name' => 'Pending Client',
        'testimonial' => 'This should not be shown yet.',
        'is_approved' => false,
        'approved_at' => null,
    ]);

    Testimonial::query()->create([
        'name' => 'Recent Client',
        'testimonial' => 'The face painting looked amazing and the kids were obsessed.',
        'is_approved' => true,
        'approved_at' => now(),
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Site/Home')
        ->has('testimonials', 2)
        ->where('testimonials.0.name', 'Recent Client')
        ->where('testimonials.0.testimonial', 'The face painting looked amazing and the kids were obsessed.')
        ->where('testimonials.1.name', 'Older Client')
        ->where('testimonials.1.testimonial', 'Melody made the party feel calm, fun, and beautifully organised.')
    );

    $homePage = file_get_contents(resource_path('js/pages/Site/Home.vue'));
    expect($homePage)->toBeString();

    $homeText = preg_replace('/\s+/', ' ', strip_tags($homePage));
    expect($homeText)
        ->toBeString()
        ->toContain('What We Do')
        ->toContain('Approved client testimonials will appear here soon.')
        ->toContain('Read more testimonials')
        ->toContain('150+')
        ->toContain('50+')
        ->toContain('Events painted')
        ->toContain('Repeat bookings')
        ->toContain('South Auckland and Northern Waikato')
        ->toContain('Face painting for parties, events, and festivals.')
        ->toContain('Baby Bump and Body Painting')
        ->toContain('Beautiful baby bump and body painting for special occasions, photoshoots, and creative event moments.')
        ->toContain('Sparkly glitter tattoos that are quick to apply and fun for all ages.')
        ->toContain('Colourful mermaid hair extensions for extra festival sparkle.')
        ->toContain('Handmade, skin-safe 3D face bling to add shimmer and shine.')
        ->not->toContain('Google Review')
        ->not->toContain('Facebook Review')
        ->not->toContain('See Facebook Reviews')
        ->not->toContain('Auckland + Franklin')
        ->not->toContain('across Auckland and Franklin');

    $homeMarkup = preg_replace('/\s+/', ' ', $homePage);
    expect($homeMarkup)
        ->toBeString()
        ->toContain('class="service-placeholder" :aria-label="`${item.title} image placeholder`"')
        ->toContain('Image placeholder')
        ->toContain('class="service-placeholder-name">{{ item.title }}</span>');
});
