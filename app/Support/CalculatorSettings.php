<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class CalculatorSettings
{
    public const KEY = 'calculator_defaults';

    /**
     * Get baseline calculator defaults.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'performance_note_sentence' => 'Last event I did about 90 paints in 4 hours — that’s 2.7 minutes per face (including mirror time and getting on/off the chair), or about 22.5 faces per hour. On average, face painters aim for around 12 per hour.',
            'artist' => [
                'name' => 'Melody',
                'email' => 'info@sprinklefairydust.co.nz',
                'website' => 'https://www.facebook.com/melfairysfacepainting/',
                'mobile' => '021 555 3921',
            ],
            'form' => [
                'organizerName' => '',
                'organizerEmail' => '',
                'eventName' => '',
                'eventDate' => '',
                'startTime' => '',
                'endTime' => '',
                'paymentType' => 'hourly',
                'rate' => 120,
                'hours' => 8,
                'pricePerFace' => 10,
                'numFaces' => 30,
                'includeSetup' => false,
                'setupRate' => 60,
                'setupHours' => 2,
                'travelType' => 'perkm',
                'distance' => 20,
                'travelRate' => 0.85,
                'flatTravel' => 0,
                'includePerformance' => false,
                'perfFaces' => 90,
                'perfHours' => 4,
                'includeGST' => true,
            ],
        ];
    }

    /**
     * Validation rules for calculator settings payloads.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'performance_note_sentence' => ['required', 'string', 'max:2000'],
            'artist' => ['required', 'array'],
            'artist.name' => ['required', 'string', 'max:255'],
            'artist.email' => ['nullable', 'string', 'max:255'],
            'artist.website' => ['nullable', 'string', 'max:2048'],
            'artist.mobile' => ['nullable', 'string', 'max:255'],
            'form' => ['required', 'array'],
            'form.organizerName' => ['nullable', 'string', 'max:255'],
            'form.organizerEmail' => ['nullable', 'string', 'max:255'],
            'form.eventName' => ['nullable', 'string', 'max:255'],
            'form.eventDate' => ['nullable', 'string', 'max:64'],
            'form.startTime' => ['nullable', 'string', 'max:16'],
            'form.endTime' => ['nullable', 'string', 'max:16'],
            'form.paymentType' => ['required', Rule::in(['hourly', 'perface'])],
            'form.rate' => ['required', 'numeric', 'min:0'],
            'form.hours' => ['required', 'numeric', 'min:0'],
            'form.pricePerFace' => ['required', 'numeric', 'min:0'],
            'form.numFaces' => ['required', 'numeric', 'min:0'],
            'form.includeSetup' => ['required', 'boolean'],
            'form.setupRate' => ['required', 'numeric', 'min:0'],
            'form.setupHours' => ['required', 'numeric', 'min:0'],
            'form.travelType' => ['required', Rule::in(['perkm', 'flat'])],
            'form.distance' => ['required', 'numeric', 'min:0'],
            'form.travelRate' => ['required', 'numeric', 'min:0'],
            'form.flatTravel' => ['required', 'numeric', 'min:0'],
            'form.includePerformance' => ['required', 'boolean'],
            'form.perfFaces' => ['required', 'numeric', 'min:0'],
            'form.perfHours' => ['required', 'numeric', 'min:0'],
            'form.includeGST' => ['required', 'boolean'],
        ];
    }

    /**
     * Merge persisted settings onto defaults.
     *
     * @param  array<string, mixed>|null  $stored
     * @return array<string, mixed>
     */
    public static function resolve(?array $stored = null): array
    {
        return array_replace_recursive(self::defaults(), $stored ?? []);
    }

    /**
     * Normalize settings for persistence.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $settings = self::resolve($input);

        return [
            'performance_note_sentence' => trim((string) $settings['performance_note_sentence']),
            'artist' => [
                'name' => trim((string) data_get($settings, 'artist.name', '')),
                'email' => trim((string) data_get($settings, 'artist.email', '')),
                'website' => trim((string) data_get($settings, 'artist.website', '')),
                'mobile' => trim((string) data_get($settings, 'artist.mobile', '')),
            ],
            'form' => [
                'organizerName' => (string) data_get($settings, 'form.organizerName', ''),
                'organizerEmail' => (string) data_get($settings, 'form.organizerEmail', ''),
                'eventName' => (string) data_get($settings, 'form.eventName', ''),
                'eventDate' => (string) data_get($settings, 'form.eventDate', ''),
                'startTime' => (string) data_get($settings, 'form.startTime', ''),
                'endTime' => (string) data_get($settings, 'form.endTime', ''),
                'paymentType' => (string) data_get($settings, 'form.paymentType', 'hourly'),
                'rate' => (float) data_get($settings, 'form.rate', 0),
                'hours' => (float) data_get($settings, 'form.hours', 0),
                'pricePerFace' => (float) data_get($settings, 'form.pricePerFace', 0),
                'numFaces' => (float) data_get($settings, 'form.numFaces', 0),
                'includeSetup' => (bool) data_get($settings, 'form.includeSetup', false),
                'setupRate' => (float) data_get($settings, 'form.setupRate', 0),
                'setupHours' => (float) data_get($settings, 'form.setupHours', 0),
                'travelType' => (string) data_get($settings, 'form.travelType', 'perkm'),
                'distance' => (float) data_get($settings, 'form.distance', 0),
                'travelRate' => (float) data_get($settings, 'form.travelRate', 0),
                'flatTravel' => (float) data_get($settings, 'form.flatTravel', 0),
                'includePerformance' => (bool) data_get($settings, 'form.includePerformance', false),
                'perfFaces' => (float) data_get($settings, 'form.perfFaces', 0),
                'perfHours' => (float) data_get($settings, 'form.perfHours', 0),
                'includeGST' => (bool) data_get($settings, 'form.includeGST', true),
            ],
        ];
    }
}
