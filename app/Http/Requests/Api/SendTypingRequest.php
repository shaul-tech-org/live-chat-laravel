<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SendTypingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sender_type' => 'required|string|in:visitor,agent',
            'sender_name' => 'required|string|max:100',
        ];
    }
}
