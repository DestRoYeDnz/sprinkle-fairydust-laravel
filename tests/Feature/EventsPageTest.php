<?php

use Inertia\Testing\AssertableInertia as Assert;

test('events page can be rendered with upcoming and past event sections', function () {
    $response = $this->get(route('events'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Site/Events'));

    $eventsPage = file_get_contents(resource_path('js/pages/Site/Events.vue'));
    expect($eventsPage)->toBeString();

    $eventsText = preg_replace('/\s+/', ' ', strip_tags($eventsPage));

    expect($eventsText)
        ->toBeString()
        ->toContain('Upcoming Events')
        ->toContain('Past Events')
        ->toContain('Event banner')
        ->toContain('Event photos')
        ->toContain('Upcoming sparkle stops and magical moments from past events.');

    expect($eventsPage)
        ->toContain("import { index as listEvents } from '@/actions/App/Http/Controllers/Api/EventController';")
        ->toContain('class="floating-logo fade-in-logo"')
        ->toContain('class="fixed inset-0 z-[120] flex items-center justify-center bg-black/60 px-4 py-6 backdrop-blur-sm"')
        ->toContain("eventRecord.visibility !== 'private'")
        ->toContain('function getEventBanner(eventRecord)')
        ->toContain('function getEventPhotos(eventRecord)')
        ->toContain('No upcoming public events right now.')
        ->toContain('No past public events to show yet.')
        ->not->toContain("eventRecord.type !== 'Private'")
        ->not->toContain('background-image: linear-gradient(to right, #ffb3e6, #ffc5d8, #ffd9b3, #d4fcb8, #b3e6ff);');
});
