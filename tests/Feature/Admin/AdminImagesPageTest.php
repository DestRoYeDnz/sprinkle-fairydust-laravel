<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admin images page includes gallery lightbox title and description fields', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.images'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Site/AdminImages'));

    $adminImagesPage = file_get_contents(resource_path('js/pages/Site/AdminImages.vue'));
    expect($adminImagesPage)->toBeString();

    $adminImagesText = preg_replace('/\s+/', ' ', strip_tags($adminImagesPage));

    expect($adminImagesText)
        ->toBeString()
        ->toContain('Upload Image')
        ->toContain('Lightbox Title')
        ->toContain('Description (optional)')
        ->toContain('Shown as overlay text in the gallery lightbox for uploaded gallery images.');

    expect($adminImagesPage)
        ->toContain("const title = ref('');")
        ->toContain("const description = ref('');")
        ->toContain("formData.append('title', title.value.trim());")
        ->toContain("formData.append('description', description.value.trim());")
        ->toContain(":required=\"collection === 'gallery'\"");
});
