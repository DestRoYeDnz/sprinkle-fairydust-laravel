<?php

use App\Models\Event;
use App\Models\GalleryImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('allows admins to create events without an extra password', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)
        ->postJson(route('admin.events.store'), [
            'name' => 'Summer Fair',
            'type' => 'Party',
            'visibility' => 'private',
            'address' => '123 Main St',
            'date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '14:00',
            'description' => 'Family event',
            'image_url' => 'https://example.com/image.jpg',
        ]);

    $response->assertOk()->assertJson(['success' => true]);

    $this->assertDatabaseHas('events', [
        'name' => 'Summer Fair',
        'type' => 'Party',
        'visibility' => 'private',
        'address' => '123 Main St',
    ]);
});

it('forbids non-admins from creating events', function () {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('admin.events.store'), [
            'type' => 'Festival',
            'visibility' => 'public',
            'date' => now()->toDateString(),
        ]);

    $response->assertForbidden();
});

it('returns only public events in the public event feed', function () {
    Event::query()->create([
        'name' => 'Public Market',
        'type' => 'Market',
        'visibility' => 'public',
        'date' => now()->addWeek()->toDateString(),
        'start_time' => '10:00',
        'end_time' => '14:00',
    ]);

    Event::query()->create([
        'name' => 'Private Party',
        'type' => 'Party',
        'visibility' => 'private',
        'address' => '123 Hidden Lane',
        'date' => now()->addWeek()->toDateString(),
        'start_time' => '15:00',
        'end_time' => '18:00',
    ]);

    $response = $this->getJson('/api/events');

    $response->assertOk();

    expect(collect($response->json())->pluck('name')->all())
        ->toContain('Public Market')
        ->not->toContain('Private Party');
});

it('returns public and private events in the admin event feed', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    Event::query()->create([
        'name' => 'Public Market',
        'type' => 'Market',
        'visibility' => 'public',
        'date' => now()->addWeek()->toDateString(),
        'start_time' => '10:00',
        'end_time' => '14:00',
    ]);

    Event::query()->create([
        'name' => 'Private Party',
        'type' => 'Party',
        'visibility' => 'private',
        'address' => '123 Hidden Lane',
        'date' => now()->addWeek()->toDateString(),
        'start_time' => '15:00',
        'end_time' => '18:00',
    ]);

    $response = $this->actingAs($admin)->getJson(route('admin.events.index'));

    $response->assertOk();

    $events = collect($response->json());

    expect($events->pluck('name')->all())
        ->toContain('Public Market')
        ->toContain('Private Party');

    expect($events->firstWhere('name', 'Private Party'))
        ->toMatchArray([
            'type' => 'Party',
            'visibility' => 'private',
            'address' => '123 Hidden Lane',
        ]);
});

it('allows admins to upload images without an extra password', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.images.upload'), [
            'title' => 'Birthday Sparkles',
            'description' => 'Rainbow face painting at a children\'s birthday party.',
            'file' => UploadedFile::fake()->image('gallery.png'),
        ]);

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('url', fn (string $url) => str_contains($url, '/storage/sprinkle/gallery/'));
    $response->assertJsonPath('title', 'Birthday Sparkles');
    $response->assertJsonPath('description', 'Rainbow face painting at a children\'s birthday party.');

    $this->assertDatabaseHas('gallery_images', [
        'id' => $response->json('id'),
        'collection' => 'gallery',
        'title' => 'Birthday Sparkles',
        'description' => 'Rainbow face painting at a children\'s birthday party.',
        'uploaded_by' => $admin->id,
    ]);

    Storage::disk('public')->assertExists('sprinkle/gallery/'.basename(parse_url($response->json('url'), PHP_URL_PATH)));
});

it('forbids non-admins from uploading images', function () {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);

    $response = $this->actingAs($user)
        ->post(route('admin.images.upload'), [
            'title' => 'Birthday Sparkles',
            'file' => UploadedFile::fake()->image('gallery.png'),
        ]);

    $response->assertForbidden();
});

it('requires a title for uploaded gallery images', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)
        ->postJson(route('admin.images.upload'), [
            'collection' => 'gallery',
            'file' => UploadedFile::fake()->image('gallery.png'),
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['title']);
});

it('stores uploads to the selected image collection', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.images.upload'), [
            'collection' => 'designs',
            'title' => 'Rainbow tiger design',
            'description' => 'Bright tiger face painting design board sample.',
            'alt_text' => 'Rainbow tiger design',
            'file' => UploadedFile::fake()->image('design.png'),
        ]);

    $response->assertOk();
    $response->assertJsonPath('collection', 'designs');
    $response->assertJsonPath('url', fn (string $url) => str_contains($url, '/storage/sprinkle/designs/'));

    $this->assertDatabaseHas('gallery_images', [
        'id' => $response->json('id'),
        'collection' => 'designs',
        'alt_text' => 'Rainbow tiger design',
        'title' => 'Rainbow tiger design',
        'description' => 'Bright tiger face painting design board sample.',
        'uploaded_by' => $admin->id,
    ]);

    Storage::disk('public')->assertExists('sprinkle/designs/'.basename(parse_url($response->json('url'), PHP_URL_PATH)));
});

it('stores event image uploads in the events collection', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.images.upload'), [
            'collection' => 'events',
            'alt_text' => 'Community day event',
            'file' => UploadedFile::fake()->image('event.png'),
        ]);

    $response->assertOk();
    $response->assertJsonPath('collection', 'events');
    $response->assertJsonPath('url', fn (string $url) => str_contains($url, '/storage/sprinkle/events/'));

    $this->assertDatabaseHas('gallery_images', [
        'id' => $response->json('id'),
        'collection' => 'events',
        'alt_text' => 'Community day event',
        'uploaded_by' => $admin->id,
    ]);

    Storage::disk('public')->assertExists('sprinkle/events/'.basename(parse_url($response->json('url'), PHP_URL_PATH)));
});

it('attaches uploaded photos to a past event and exposes them in event and gallery feeds', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $pastEvent = Event::query()->create([
        'name' => 'Past Community Fair',
        'type' => 'Festival',
        'address' => '12 Example Road',
        'date' => now()->subWeek()->toDateString(),
        'start_time' => '10:00',
        'end_time' => '13:00',
        'description' => 'A finished community event.',
    ]);

    Event::query()->create([
        'name' => 'Upcoming Market',
        'type' => 'Market',
        'address' => '99 Future Lane',
        'date' => now()->addWeek()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '12:00',
        'description' => 'An upcoming market event.',
    ]);

    GalleryImage::query()->create([
        'collection' => 'gallery',
        'url' => '/storage/sprinkle/gallery/hero.jpg',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    GalleryImage::query()->create([
        'collection' => 'events',
        'url' => '/storage/sprinkle/events/cover-only.jpg',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.images.upload'), [
            'collection' => 'events',
            'event_id' => $pastEvent->id,
            'alt_text' => 'Past event sparkle photo',
            'file' => UploadedFile::fake()->image('past-event-photo.png'),
        ]);

    $response->assertOk();
    $response->assertJsonPath('collection', 'events');
    $response->assertJsonPath('event_id', $pastEvent->id);

    $this->assertDatabaseHas('gallery_images', [
        'id' => $response->json('id'),
        'collection' => 'events',
        'event_id' => $pastEvent->id,
        'alt_text' => 'Past event sparkle photo',
        'uploaded_by' => $admin->id,
    ]);

    Storage::disk('public')->assertExists('sprinkle/events/'.basename(parse_url($response->json('url'), PHP_URL_PATH)));

    $eventsResponse = $this->getJson('/api/events');

    $eventsResponse->assertOk();

    $pastEventPayload = collect($eventsResponse->json())->firstWhere('id', $pastEvent->id);

    expect($pastEventPayload)
        ->not->toBeNull()
        ->and($pastEventPayload['photos'])
        ->toHaveCount(1)
        ->and($pastEventPayload['photos'][0]['event_id'])
        ->toBe($pastEvent->id)
        ->and($pastEventPayload['photos'][0]['url'])
        ->toBe($response->json('url'));

    $galleryResponse = $this->getJson('/api/gallery-images?collection=gallery');

    $galleryResponse->assertOk();

    $galleryPayload = collect($galleryResponse->json());

    expect($galleryPayload->pluck('url')->all())
        ->toContain('/storage/sprinkle/gallery/hero.jpg')
        ->toContain($response->json('url'))
        ->not->toContain('/storage/sprinkle/events/cover-only.jpg');

    $galleryEventImage = $galleryPayload->firstWhere('url', $response->json('url'));

    expect($galleryEventImage)
        ->not->toBeNull()
        ->and($galleryEventImage['event_id'])
        ->toBe($pastEvent->id)
        ->and($galleryEventImage['collection'])
        ->toBe('events')
        ->and($galleryEventImage['event']['id'])
        ->toBe($pastEvent->id)
        ->and($galleryEventImage['event']['name'])
        ->toBe('Past Community Fair')
        ->and($galleryEventImage['event']['type'])
        ->toBe('Festival');

    $eventGalleryResponse = $this->getJson('/api/gallery-images?collection=events');

    $eventGalleryResponse->assertOk();
    $eventGalleryResponse->assertJsonCount(1);
    $eventGalleryResponse->assertJsonPath('0.url', $response->json('url'));
    $eventGalleryResponse->assertJsonPath('0.event_id', $pastEvent->id);
});

it('rejects event photo uploads for current or future events', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $futureEvent = Event::query()->create([
        'name' => 'Future Community Fair',
        'type' => 'Festival',
        'address' => '12 Example Road',
        'date' => now()->addDay()->toDateString(),
        'start_time' => '10:00',
        'end_time' => '13:00',
        'description' => 'A future community event.',
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.images.upload'), [
            'collection' => 'events',
            'event_id' => $futureEvent->id,
            'file' => UploadedFile::fake()->image('future-event-photo.png'),
        ]);

    $response->assertUnprocessable();
    $response->assertJsonPath('message', 'Event photos can only be added to past events.');

    expect(GalleryImage::query()->where('event_id', $futureEvent->id)->exists())->toBeFalse();
});

it('returns only active images for the requested collection', function () {
    GalleryImage::query()->create([
        'collection' => 'gallery',
        'url' => '/storage/sprinkle/gallery/a.jpg',
        'title' => 'Rainbow butterfly',
        'description' => 'Pastel rainbow butterfly face paint.',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    GalleryImage::query()->create([
        'collection' => 'gallery',
        'url' => '/storage/sprinkle/gallery/b.jpg',
        'title' => 'Tiger roar',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    GalleryImage::query()->create([
        'collection' => 'gallery',
        'url' => '/storage/sprinkle/gallery/hidden.jpg',
        'sort_order' => 3,
        'is_active' => false,
    ]);

    GalleryImage::query()->create([
        'collection' => 'designs',
        'url' => '/storage/sprinkle/designs/only-design.jpg',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $galleryResponse = $this->getJson('/api/gallery-images?collection=gallery');

    $galleryResponse->assertOk();
    $galleryResponse->assertJsonCount(2);
    $galleryResponse->assertJsonPath('0.url', '/storage/sprinkle/gallery/b.jpg');
    $galleryResponse->assertJsonPath('0.title', 'Tiger roar');
    $galleryResponse->assertJsonPath('1.url', '/storage/sprinkle/gallery/a.jpg');
    $galleryResponse->assertJsonPath('1.description', 'Pastel rainbow butterfly face paint.');

    $designsResponse = $this->getJson('/api/gallery-images?collection=designs');

    $designsResponse->assertOk();
    $designsResponse->assertJsonCount(1);
    $designsResponse->assertJsonPath('0.url', '/storage/sprinkle/designs/only-design.jpg');
});
