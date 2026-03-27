<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:100',
            'domain' => 'nullable|string|max:255',
            'widget_config' => 'nullable|array',
            'auto_reply_message' => 'nullable|string',
            'telegram_chat_id' => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
