<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class MarkReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reader_type' => 'required|string|in:visitor,agent',
            'reader_name' => 'required|string|max:100',
        ];
    }
}
