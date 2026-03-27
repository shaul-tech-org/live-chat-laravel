<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sender_type' => 'required|string|in:visitor,agent,system',
            'sender_name' => 'required|string|max:100',
            'content' => 'required|string|max:5000',
            'content_type' => 'required|string|in:text,image,file',
            'file_url' => 'nullable|string|max:500',
            'reply_to' => 'nullable|array',
            'reply_to.id' => 'nullable|string',
            'reply_to.content' => 'nullable|string',
        ];
    }
}
