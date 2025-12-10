<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => 'sometimes|string|max:100',
            'username' => [
                'sometimes',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users')->ignore($userId),
            ],
            'email' => [
                'sometimes',
                'email',
                'max:100',
                Rule::unique('users')->ignore($userId),
            ],
            'bio' => 'sometimes|nullable|string|max:150',
            'profile_picture' => 'sometimes|nullable|file|mimes:jpg,jpeg,png|max:2048',
            'password' => [
                'sometimes',
                'nullable',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols()
            ],
        ];
    }
}
