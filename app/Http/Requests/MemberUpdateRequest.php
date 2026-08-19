<?php

namespace App\Http\Requests;

use App\Rules\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberUpdateRequest extends FormRequest
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
            'target_email' => ['required', Rule::email()],
            'name' => ['nullable', Rule::string()->min(2)],
            'email' => ['nullable', Rule::email()],
            'password' => ['nullable', Rule::string()->min(8)],
            'phone' => ['nullable', new Phone],
            'address' => ['nullable', Rule::string()],
            'active' => ['nullable', 'boolean']
        ];
    }
}
