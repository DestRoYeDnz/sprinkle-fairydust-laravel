<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders admin events management with past event photo uploads', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.events'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Site/AdminEvents'));

    $adminEventsPage = file_get_contents(resource_path('js/pages/Site/AdminEvents.vue'));
    expect($adminEventsPage)->toBeString();

    $adminEventsText = preg_replace('/\s+/', ' ', strip_tags($adminEventsPage));

    expect($adminEventsText)
        ->toBeString()
        ->toContain('Manage Events')
        ->toContain('Event Banner Upload')
        ->toContain('Banner preview')
        ->toContain('Event Visibility')
        ->toContain('Future Events')
        ->toContain('Past Events')
        ->toContain('Upload extra photos from this event after it is finished.');

    expect($adminEventsPage)
        ->toContain("import { adminIndex as listEvents, store as createEvent } from '@/actions/App/Http/Controllers/Api/EventController';")
        ->toContain('const allTimes = [')
        ->toContain('const endTimeOptions = computed(() => {')
        ->toContain("visibility: 'public'")
        ->toContain('function formatVisibility(visibility)')
        ->toContain('Select start time')
        ->toContain('Select end time')
        ->toContain('function getEventPhotoCount(eventRecord)')
        ->toContain('function getSelectedEventPhotoCount(eventId)')
        ->toContain('Array.from(eventInput.target.files ?? [])')
        ->toContain("formData.append('event_id', String(eventRecord.id));")
        ->toContain('function uploadPastEventPhoto(eventRecord)')
        ->toContain('type="file"')
        ->toContain('multiple')
        ->toContain('<option>Party</option>')
        ->toContain('<option value="private">Private</option>')
        ->toContain('Upload Photos')
        ->toContain('Address (optional)')
        ->not->toContain('Banner Image URL')
        ->not->toContain('Address (optional for Private events)')
        ->not->toContain('public gallery');
});
