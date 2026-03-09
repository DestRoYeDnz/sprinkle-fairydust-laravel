<?php

use Inertia\Testing\AssertableInertia as Assert;

test('quote page can be rendered with the updated service options', function () {
    $response = $this->get(route('quote'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Site/Quote'));

    $quotePage = file_get_contents(resource_path('js/pages/Site/Quote.vue'));
    expect($quotePage)->toBeString();

    $quoteText = preg_replace('/\s+/', ' ', strip_tags($quotePage));

    expect($quoteText)
        ->toBeString()
        ->toContain('Face Painting for parties, events, and festivals')
        ->toContain('Baby bump painting and body painting')
        ->toContain('Glitter tattoos')
        ->toContain('Mermaid hair extensions')
        ->toContain('Handmade, skin-safe 3D face bling');

    expect($quotePage)
        ->toContain("window.location.assign(data.redirect_url || '/quote/overview');");
});

test('quote overview page can be rendered with the submission message', function () {
    $response = $this->get(route('quote.overview'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Site/QuoteOverview'));

    $overviewPage = file_get_contents(resource_path('js/pages/Site/QuoteOverview.vue'));
    expect($overviewPage)->toBeString();

    $overviewText = preg_replace('/\s+/', ' ', strip_tags($overviewPage));

    expect($overviewText)
        ->toBeString()
        ->toContain('Quote Submitted')
        ->toContain('Your quote has been submitted.')
        ->toContain('The Sprinkle Fairydust fairies are processing it and will get back to you soon.')
        ->toContain('Submit Another Quote');
});
