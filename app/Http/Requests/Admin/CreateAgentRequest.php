<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|uuid',
            'user_id' => 'required|string|max:255',
            'name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'role' => 'required|string|in:admin,agent',
        ];
    }
}
