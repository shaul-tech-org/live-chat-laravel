<?php

namespace App\Http\Requests\Api;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SubmitEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string', new Enum(EventType::class)],
            'page_url' => 'nullable|string|max:2048',
            'metadata' => 'nullable|array',
        ];
    }
}
