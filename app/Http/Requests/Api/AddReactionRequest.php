<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AddReactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message_id' => 'required|string|max:64',
            'emoji' => 'required|string|max:32',
            'user_id' => 'required|string|max:64',
        ];
    }
}
