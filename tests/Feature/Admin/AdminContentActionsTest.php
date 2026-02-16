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
            'type' => 'Festival',
            'address' => '123 Main St',
            'date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '14:00',
            'description' => 'Family event',
            'image_url' => 'https://example.com/image.jpg',
        ]);

    $response->assertOk()->assertJson(['success' => true]);

    expect(Event::query()->where('name', 'Summer Fair')->exists())->toBeTrue();
});

it('forbids non-admins from creating events', function () {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('admin.events.store'), [
            'type' => 'Festival',
            'date' => now()->toDateString(),
        ]);

    $response->assertForbidden();
});

it('allows admins to upload images without an extra password', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.images.upload'), [
            'file' => UploadedFile::fake()->image('gallery.png'),
        ]);

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('url', fn (string $url) => str_contains($url, '/storage/sprinkle/gallery/'));

    $this->assertDatabaseHas('gallery_images', [
        'id' => $response->json('id'),
        'collection' => 'gallery',
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
            'file' => UploadedFile::fake()->image('gallery.png'),
        ]);

    $response->assertForbidden();
});

it('stores uploads to the selected image collection', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.images.upload'), [
            'collection' => 'designs',
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

it('returns only active images for the requested collection', function () {
    GalleryImage::query()->create([
        'collection' => 'gallery',
        'url' => '/storage/sprinkle/gallery/a.jpg',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    GalleryImage::query()->create([
        'collection' => 'gallery',
        'url' => '/storage/sprinkle/gallery/b.jpg',
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
    $galleryResponse->assertJsonPath('1.url', '/storage/sprinkle/gallery/a.jpg');

    $designsResponse = $this->getJson('/api/gallery-images?collection=designs');

    $designsResponse->assertOk();
    $designsResponse->assertJsonCount(1);
    $designsResponse->assertJsonPath('0.url', '/storage/sprinkle/designs/only-design.jpg');
});
