<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PropertyRequest extends FormRequest
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
        $rules = [
            'category_id' => 'required|exists:categories,id',
            'location_id' => 'required|exists:locations,id',
            'property_name' => 'required|string|max:255',
            'estimated_monthly' => 'nullable|numeric|min:0|max:999999.99',
            'lot_area' => 'nullable|numeric|min:0|max:999999.99',
            'floor_area' => 'nullable|numeric|min:0|max:999999.99',
            'details' => 'nullable|string|max:65535',
            'status' => 'required|in:Renovation,Rented,Available',
            'is_featured' => 'boolean',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max per image
            'replace_images' => 'boolean',
        ];

        // For update requests, make some fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['category_id'] = 'sometimes|required|exists:categories,id';
            $rules['location_id'] = 'sometimes|required|exists:locations,id';
            $rules['property_name'] = 'sometimes|required|string|max:255';
            $rules['status'] = 'sometimes|required|in:Renovation,Rented,Available';
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'location_id' => 'location',
            'property_name' => 'property name',
            'estimated_monthly' => 'estimated monthly rent',
            'lot_area' => 'lot area',
            'floor_area' => 'floor area',
            'is_featured' => 'featured status',
            'images.*' => 'image',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category for the property.',
            'category_id.exists' => 'The selected category is invalid.',
            'location_id.required' => 'Please select a location for the property.',
            'location_id.exists' => 'The selected location is invalid.',
            'property_name.required' => 'Property name is required.',
            'property_name.max' => 'Property name cannot exceed 255 characters.',
            'estimated_monthly.numeric' => 'Estimated monthly rent must be a valid number.',
            'estimated_monthly.min' => 'Estimated monthly rent cannot be negative.',
            'estimated_monthly.max' => 'Estimated monthly rent cannot exceed 999,999.99.',
            'lot_area.numeric' => 'Lot area must be a valid number.',
            'lot_area.min' => 'Lot area cannot be negative.',
            'lot_area.max' => 'Lot area cannot exceed 999,999.99.',
            'floor_area.numeric' => 'Floor area must be a valid number.',
            'floor_area.min' => 'Floor area cannot be negative.',
            'floor_area.max' => 'Floor area cannot exceed 999,999.99.',
            'details.max' => 'Details cannot exceed 65,535 characters.',
            'status.required' => 'Please select a status for the property.',
            'status.in' => 'Status must be one of: Renovation, Rented, or Available.',
            'images.array' => 'Images must be provided as an array.',
            'images.max' => 'You can upload a maximum of 10 images.',
            'images.*.image' => 'Each file must be a valid image.',
            'images.*.mimes' => 'Images must be in JPEG, PNG, JPG, GIF, or WebP format.',
            'images.*.max' => 'Each image cannot exceed 5MB in size.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string 'true'/'false' to boolean for is_featured
        if ($this->has('is_featured')) {
            $this->merge([
                'is_featured' => filter_var($this->is_featured, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Convert string 'true'/'false' to boolean for replace_images
        if ($this->has('replace_images')) {
            $this->merge([
                'replace_images' => filter_var($this->replace_images, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Convert empty strings to null for nullable numeric fields
        $numericFields = ['estimated_monthly', 'lot_area', 'floor_area'];
        foreach ($numericFields as $field) {
            if ($this->has($field) && $this->$field === '') {
                $this->merge([$field => null]);
            }
        }

        // Convert empty string to null for details
        if ($this->has('details') && $this->details === '') {
            $this->merge(['details' => null]);
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        if ($this->expectsJson()) {
            $response = response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);

            throw new \Illuminate\Http\Exceptions\HttpResponseException($response);
        }

        parent::failedValidation($validator);
    }
}
