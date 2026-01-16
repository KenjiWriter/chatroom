<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('admin.access') || $this->user()->hasPermission('bypass.level_lock');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('ranks', 'name')->ignore($this->rank)],
            'priority' => ['sometimes', 'integer', 'min:0'],
            'prefix' => ['nullable', 'string', 'max:32'],
            'color_prefix' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'color_name' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'color_text' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'effects' => ['nullable', 'array'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }
}
