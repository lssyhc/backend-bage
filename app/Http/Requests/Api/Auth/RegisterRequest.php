<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|alpha_dash:ascii|unique:users,username',
            'email' => 'required|string|email:dns|max:100|unique:users,email',
            'password' => [
                'required',
                'string',
                'max:100',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama lengkap tidak boleh lebih dari 100 karakter.',
            'username.required' => 'Username wajib diisi.',
            'username.max' => 'Username tidak boleh lebih dari 50 karakter.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, strip, dan garis bawah.',
            'username.unique' => 'Username sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid (pastikan menggunakan @ dan domain).',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.max' => 'Password tidak boleh lebih dari 100 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
            'password.uncompromised' => 'Password ini terdeteksi dalam kebocoran data. Silakan gunakan password lain.',
        ];
    }
}
