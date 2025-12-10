<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'address' => 'required|string|max:150',
            'description' => 'required|string|max:150',
            'category_id' => 'required|exists:categories,id',
            'latitude' => ['required', 'numeric', 'between:-11,6'],
            'longitude' => ['required', 'numeric', 'between:95,141'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.between' => 'Lokasi harus berada di dalam wilayah Indonesia (Lintang -11 s.d 6).',
            'longitude.between' => 'Lokasi harus berada di dalam wilayah Indonesia (Bujur 95 s.d 141).',
        ];
    }
}
