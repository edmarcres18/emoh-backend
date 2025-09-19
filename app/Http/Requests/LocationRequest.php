<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
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
        $locationId = $this->route('location') ? $this->route('location')->id : null;

        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:locations,code,' . $locationId,
            'description' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The location name is required.',
            'name.string' => 'The location name must be a string.',
            'name.max' => 'The location name may not be greater than 255 characters.',
            'code.required' => 'The location code is required.',
            'code.string' => 'The location code must be a string.',
            'code.max' => 'The location code may not be greater than 255 characters.',
            'code.unique' => 'The location code has already been taken.',
            'description.string' => 'The description must be a string.',
        ];
    }
}
