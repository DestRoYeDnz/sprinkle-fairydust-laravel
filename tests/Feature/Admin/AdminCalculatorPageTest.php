<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('passes accepted terms between admin quotes and the calculator', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.calculator'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Site/AdminCalculator'));

    $adminQuotesPage = file_get_contents(resource_path('js/pages/Site/AdminQuotes.vue'));
    $adminCalculatorPage = file_get_contents(resource_path('js/pages/Site/AdminCalculator.vue'));

    expect($adminQuotesPage)
        ->toBeString()
        ->toContain("params.set('terms_accepted', '1');");

    expect($adminCalculatorPage)
        ->toBeString()
        ->toContain("const termsAccepted = params.get('terms_accepted') ?? '';")
        ->toContain("form.value.termsAccepted = ['1', 'true', 'yes'].includes(termsAccepted.toLowerCase());");
});
