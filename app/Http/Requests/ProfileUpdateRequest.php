<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar_url' => ['nullable', 'url', 'max:255'],
            'banner_url' => ['nullable', 'url', 'max:255'],
            'is_private' => ['boolean'],
            'preferences' => ['nullable', 'array'],
            'preferences.show_online_status' => ['boolean'],
            'preferences.show_current_room' => ['boolean'],
        ];
    }
}
