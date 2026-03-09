<?php

use Inertia\Testing\AssertableInertia as Assert;

test('gallery page renders uploaded gallery images with lightbox overlay metadata', function () {
    $response = $this->get(route('gallery'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Site/Gallery'));

    $galleryPage = file_get_contents(resource_path('js/pages/Site/Gallery.vue'));
    expect($galleryPage)->toBeString();

    $galleryText = preg_replace('/\s+/', ' ', strip_tags($galleryPage));

    expect($galleryText)
        ->toBeString()
        ->toContain('Our Gallery')
        ->toContain('Click on any image to see it larger.')
        ->toContain('Sprinkle Fairydust Gallery');

    expect($galleryPage)
        ->toContain("fetch('/api/gallery-images?collection=gallery')")
        ->toContain('function normalizeImage(imageRecord, index)')
        ->toContain('function isEventImage(imageRecord)')
        ->toContain('function getImageEyebrow(imageRecord)')
        ->toContain('function getImageTitle(imageRecord)')
        ->toContain('function getImageMeta(imageRecord)')
        ->toContain('function getImageDescription(imageRecord)')
        ->toContain('class="lightbox-overlay"')
        ->toContain('class="lightbox-eyebrow"')
        ->toContain('class="lightbox-meta"')
        ->toContain('class="lightbox-title"')
        ->toContain('class="lightbox-description"')
        ->not->toContain('item?.url)');
});
