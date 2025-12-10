<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => 'required|exists:locations,id',
            'content' => 'required|string|max:150',
            'rating' => 'nullable|integer|min:1|max:5',
            'media' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ];
    }
}
