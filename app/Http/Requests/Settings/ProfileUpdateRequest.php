<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->profileRules($this->user()->id), [
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar_url' => ['nullable', 'string', 'url', 'max:2048'],
            'banner_url' => ['nullable', 'string', 'url', 'max:2048'],
            'is_private' => ['boolean'],
            'preferences' => ['nullable', 'array'],
            'preferences.show_online_status' => ['boolean'],
            'preferences.show_current_room' => ['boolean'],
        ]);
    }
}
