<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'domain' => 'nullable|string|max:255',
            'owner_id' => 'required|string|max:255',
            'widget_config' => 'nullable|array',
            'auto_reply_message' => 'nullable|string',
            'telegram_chat_id' => 'nullable|integer',
        ];
    }
}
