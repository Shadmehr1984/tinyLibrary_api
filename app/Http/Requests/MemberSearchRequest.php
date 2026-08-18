<?php

namespace App\Http\Requests;

use App\Rules\Date;
use App\Rules\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberSearchRequest extends FormRequest
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
            'name' => ['nullable', Rule::string()],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', new Phone],
            'address' => ['nullable', Rule::string()],
            'membership_date' => ['nullable', new Date],
            'membership_date_before' => ['nullable', new Date],
            'membership_date_after' => ['nullable', new Date],
            'active' => ['nullable', Rule::in(true, false)],
            'penalty_balance' => ['nullable', 'integer', 'gt:0'],
            'penalty_balance_lower_than' => ['nullable', 'integer', 'gt:0'],
            'penalty_balance_greater_than' => ['nullable', 'integer', 'gt:0'],
            '_check' => ['required_without_all:name,email,phone,address,membership_date,membership_date_before,'.
            'membership_date_after,active,penalty_balance']
        ];
    }

    public function prepareForValidation(){
        $this->merge([
            '_check' => 'dummy'
        ]);
    }
}
