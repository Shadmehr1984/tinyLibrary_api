<?php

namespace App\Http\Requests;

use App\Rules\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
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
            'name' => ['required', Rule::string()->min(2)],
            'email' => ['required', Rule::email()],
            'password' => ['required', Rule::string()->min(8)],
            'phone' => ['required', new Phone],
            'address' => ['required', Rule::string()],
        ];
    }
}
