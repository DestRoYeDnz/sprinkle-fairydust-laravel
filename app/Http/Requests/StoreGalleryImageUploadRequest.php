<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGalleryImageUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'image', 'max:10240'],
            'collection' => ['nullable', Rule::in(['gallery', 'designs', 'events'])],
            'title' => [
                Rule::requiredIf(function (): bool {
                    if ($this->filled('event_id')) {
                        return false;
                    }

                    $collection = $this->string('collection')->toString();

                    return $collection === '' || $collection === 'gallery';
                }),
                'nullable',
                'string',
                'max:255',
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please add a title for uploaded gallery images.',
        ];
    }
}
