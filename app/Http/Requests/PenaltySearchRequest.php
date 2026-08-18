<?php

namespace App\Http\Requests;

use App\Rules\Date;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PenaltySearchRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'borrowed_id' => ['nullable', 'integer', 'gt:0'],
            'member_id' => ['nullable', 'integer', 'gt:0'],
            'amount' => ['nullable', 'integer', 'gt:0'],
            'amount_lower_than' => ['nullable', 'integer', 'gt:0'],
            'amount_greater_than' => ['nullable', 'integer', 'gt:0'],
            'calculated_at' => ['nullable', new Date],
            'calculated_before_at' => ['nullable', new Date],
            'calculated_after_at' => ['nullable', new Date],
            'paid_at' => ['nullable', new Date],
            'paid_before_at' => ['nullable', new Date],
            'paid_after_at' => ['nullable', new Date],
            '_check' => ['required_without_all:borrowed_id,member_id,amount,amount_lower_than,amount_greater_than,'.
            'calculated_at,calculated_before_at,calculated_after_at,paid_at,paid_before_at,paid_after_at'] 
        ];
    }

    public function prepareForValidation(){
        $this->merge([
            '_check' => 'dummy'
        ]);
    }
}
