<?php

namespace App\Http\Requests;

use App\Rules\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LibrarianSearchRequest extends FormRequest
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
            'name' => ['nullable', Rule::string()->min(2)],
            'email' => ['nullable', Rule::email()],
            'phone' => ['nullable', new Phone],
            'address' => ['nullable', Rule::string()],
            '_check' => ['required_without_all:name,email,phone,address']
        ];
    }

    public function prepareForValidation(){
        $this->merge([
            '_check' => 'dummy'
        ]);
    }
}
