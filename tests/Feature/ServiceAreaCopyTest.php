<?php

test('public pages use the updated south auckland and northern waikato service area copy', function () {
    $publicPages = [
        resource_path('js/pages/Site/About.vue'),
        resource_path('js/pages/Site/Designs.vue'),
        resource_path('js/pages/Site/Events.vue'),
        resource_path('js/pages/Site/FacePainting.vue'),
        resource_path('js/pages/Site/Faq.vue'),
        resource_path('js/pages/Site/FestivalFacePainting.vue'),
        resource_path('js/pages/Site/Gallery.vue'),
        resource_path('js/pages/Site/GlitterTattoos.vue'),
        resource_path('js/pages/Site/Home.vue'),
        resource_path('js/pages/Site/Quote.vue'),
        resource_path('js/pages/Site/Services.vue'),
        resource_path('js/pages/Site/Terms.vue'),
        resource_path('js/pages/Site/Testimonials.vue'),
    ];

    foreach ($publicPages as $file) {
        $content = file_get_contents($file);

        expect($content)
            ->toBeString()
            ->toContain('South Auckland and Northern Waikato')
            ->not->toContain('Auckland + Franklin')
            ->not->toContain('Auckland and Franklin')
            ->not->toContain('Pukekohe, Papakura, Drury and Auckland');
    }
});

test('travel area placeholders use the updated service region wording', function () {
    $quotePage = file_get_contents(resource_path('js/pages/Site/Quote.vue'));
    $adminCalculatorPage = file_get_contents(resource_path('js/pages/Site/AdminCalculator.vue'));

    expect($quotePage)
        ->toBeString()
        ->toContain('placeholder="South Auckland or Northern Waikato"')
        ->not->toContain('placeholder="Pukekohe, Papakura, etc"');

    expect($adminCalculatorPage)
        ->toBeString()
        ->toContain('placeholder="e.g. South Auckland Summer Fair"')
        ->toContain('placeholder="e.g. South Auckland / Northern Waikato"')
        ->not->toContain('Franklin Summer Fair')
        ->not->toContain('Pukekohe / Franklin');
});
