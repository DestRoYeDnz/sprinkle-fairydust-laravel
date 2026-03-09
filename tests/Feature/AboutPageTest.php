<?php

use Inertia\Testing\AssertableInertia as Assert;

test('about page can be rendered with the updated about message', function () {
    $response = $this->get(route('about'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Site/About'));

    $aboutPage = file_get_contents(resource_path('js/pages/Site/About.vue'));
    expect($aboutPage)->toBeString();

    $aboutText = preg_replace('/\s+/', ' ', strip_tags($aboutPage));
    expect($aboutText)
        ->toBeString()
        ->toContain('Hi, I’m Melody.')
        ->toContain('I have now been face painting professionally for six years, and I absolutely')
        ->toContain('When you book me, you get someone who loves bold colour and sparkly glitter,');

    $aboutMarkup = preg_replace('/\s+/', ' ', $aboutPage);
    expect($aboutMarkup)
        ->toBeString()
        ->toContain('class="mt-8 flex justify-center"')
        ->toContain('class="cta inline-flex items-center justify-center rounded-2xl bg-white px-6 py-3 font-bold text-gray-900 shadow-lg transition hover:scale-105"');
});
