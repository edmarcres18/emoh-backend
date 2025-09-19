<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RentedRequest extends FormRequest
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
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'monthly_rent' => [
                'required', 
                'numeric', 
                'min:0', 
                'max:999999.99',
                function ($attribute, $value, $fail) {
                    if ($this->property_id) {
                        $property = \App\Models\Property::find($this->property_id);
                        if ($property && $property->estimated_monthly) {
                            // Convert to float for comparison to handle string/numeric type issues
                            $monthlyRent = (float) $value;
                            $estimatedMonthly = (float) $property->estimated_monthly;
                            
                            // Allow small floating point differences (0.01)
                            if (abs($monthlyRent - $estimatedMonthly) > 0.01) {
                                $fail("Monthly rent must match the property's estimated monthly rate of ₱" . number_format($estimatedMonthly, 2) . ".");
                            }
                        }
                    }
                }
            ],
            'security_deposit' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'status' => ['required', Rule::in(['active'])],
            'terms_conditions' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['string', 'max:255'],
            'contract_signed_at' => ['nullable', 'date'],
        ];

        // For updates, allow start_date to be in the past
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['start_date'] = ['required', 'date'];
        }

        // Additional validation for property availability
        if ($this->isMethod('POST') || ($this->isMethod('PUT') && $this->property_id != $this->route('rented')?->property_id)) {
            $rules['property_id'][] = function ($attribute, $value, $fail) {
                $query = \App\Models\Rented::where('property_id', $value)
                    ->where('status', 'active');

                // Exclude current record for updates
                if ($this->route('rented')) {
                    $query->where('id', '!=', $this->route('rented')->id);
                }

                if ($query->exists()) {
                    $fail('This property is already rented and active.');
                }
            };
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Auto-set monthly_rent from property's estimated_monthly if not provided
        if ($this->property_id && !$this->monthly_rent) {
            $property = \App\Models\Property::find($this->property_id);
            if ($property && $property->estimated_monthly) {
                $this->merge([
                    'monthly_rent' => $property->estimated_monthly
                ]);
            }
        }

        // Force status to always be 'active'
        $this->merge(['status' => 'active']);

        // Format dates properly for validation
        if ($this->start_date) {
            $this->merge(['start_date' => date('Y-m-d', strtotime($this->start_date))]);
        }
        if ($this->end_date) {
            $this->merge(['end_date' => date('Y-m-d', strtotime($this->end_date))]);
        }
        if ($this->contract_signed_at) {
            $this->merge(['contract_signed_at' => date('Y-m-d', strtotime($this->contract_signed_at))]);
        }
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Please select a client.',
            'client_id.exists' => 'The selected client does not exist.',
            'property_id.required' => 'Please select a property.',
            'property_id.exists' => 'The selected property does not exist.',
            'monthly_rent.required' => 'Monthly rent is required.',
            'monthly_rent.numeric' => 'Monthly rent must be a valid number.',
            'monthly_rent.min' => 'Monthly rent cannot be negative.',
            'monthly_rent.max' => 'Monthly rent cannot exceed ₱999,999.99.',
            'security_deposit.numeric' => 'Security deposit must be a valid number.',
            'security_deposit.min' => 'Security deposit cannot be negative.',
            'security_deposit.max' => 'Security deposit cannot exceed ₱999,999.99.',
            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Start date must be a valid date.',
            'start_date.after_or_equal' => 'Start date must be today or in the future.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after' => 'End date must be after the start date.',
            'status.required' => 'Status is required.',
            'status.in' => 'Invalid status selected.',
            'terms_conditions.max' => 'Terms and conditions cannot exceed 5000 characters.',
            'notes.max' => 'Notes cannot exceed 2000 characters.',
            'documents.array' => 'Documents must be an array.',
            'contract_signed_at.date' => 'Contract signed date must be a valid date.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'client',
            'property_id' => 'property',
            'monthly_rent' => 'monthly rent',
            'security_deposit' => 'security deposit',
            'start_date' => 'start date',
            'end_date' => 'end date',
            'terms_conditions' => 'terms and conditions',
            'contract_signed_at' => 'contract signed date',
        ];
    }
}
