<?php

use App\Models\AdminSetting;
use App\Models\User;
use App\Support\CalculatorSettings;

it('returns calculator settings defaults for admin users', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)->getJson(route('admin.settings.calculator.show'));

    $response->assertOk();
    $response->assertJsonPath('artist.name', 'Melody');
    $response->assertJsonPath('form.paymentType', 'hourly');
    $response->assertJsonPath('form.rate', 120);
});

it('allows admin users to update calculator settings', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $payload = [
        'performance_note_sentence' => 'Fast painter benchmark.',
        'artist' => [
            'name' => 'Sprinkle Team',
            'email' => 'team@example.com',
            'website' => 'https://example.com',
            'mobile' => '021 000 0000',
        ],
        'form' => [
            'organizerName' => 'Sample Organizer',
            'organizerEmail' => 'organizer@example.com',
            'eventName' => 'Sample Event',
            'eventDate' => '2026-03-04',
            'startTime' => '09:00',
            'endTime' => '13:00',
            'paymentType' => 'perface',
            'rate' => 130,
            'hours' => 4,
            'pricePerFace' => 15,
            'numFaces' => 45,
            'includeSetup' => true,
            'setupRate' => 65,
            'setupHours' => 1.5,
            'travelType' => 'flat',
            'distance' => 12,
            'travelRate' => 0.95,
            'flatTravel' => 40,
            'includePerformance' => true,
            'perfFaces' => 100,
            'perfHours' => 4,
            'includeGST' => false,
        ],
    ];

    $response = $this->actingAs($admin)
        ->putJson(route('admin.settings.calculator.update'), $payload);

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('settings.artist.name', 'Sprinkle Team');
    $response->assertJsonPath('settings.form.paymentType', 'perface');
    $response->assertJsonPath('settings.form.includeGST', false);

    $setting = AdminSetting::query()
        ->where('key', CalculatorSettings::KEY)
        ->first();

    expect($setting)->not->toBeNull();
    expect(data_get($setting?->value, 'artist.name'))->toBe('Sprinkle Team');
    expect(data_get($setting?->value, 'form.travelType'))->toBe('flat');
    expect(data_get($setting?->value, 'form.flatTravel'))->toBe(40.0);
});

it('forbids non-admin users from calculator settings endpoints', function () {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);

    $this->actingAs($user)
        ->getJson(route('admin.settings.calculator.show'))
        ->assertForbidden();

    $this->actingAs($user)
        ->putJson(route('admin.settings.calculator.update'), CalculatorSettings::defaults())
        ->assertForbidden();
});

