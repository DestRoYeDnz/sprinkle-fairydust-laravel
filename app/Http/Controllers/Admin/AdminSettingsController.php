<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use App\Support\CalculatorSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    /**
     * Return calculator defaults and saved values.
     */
    public function showCalculator(): JsonResponse
    {
        $stored = AdminSetting::query()
            ->where('key', CalculatorSettings::KEY)
            ->first()?->value;

        return response()
            ->json(CalculatorSettings::resolve($stored))
            ->header('Cache-Control', 'no-cache');
    }

    /**
     * Persist calculator prefill settings.
     */
    public function updateCalculator(Request $request): JsonResponse
    {
        $validated = $request->validate(CalculatorSettings::rules());
        $normalized = CalculatorSettings::normalize($validated);

        AdminSetting::query()->updateOrCreate(
            ['key' => CalculatorSettings::KEY],
            ['value' => $normalized],
        );

        return response()->json([
            'success' => true,
            'settings' => $normalized,
        ]);
    }
}

