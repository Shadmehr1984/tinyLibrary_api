<?php

namespace App\Http\Requests;

use App\Rules\Date;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PenaltyRequest extends FormRequest
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
            'borrowed_id' => ['required', 'integer', 'gt:0', Rule::exists('penalties')],
            'member_id' => [
                'required',
                'integer',
                Rule::unique('penalties')->where('borrowed_id', $this->borrowed_id)
            ],
            'amount' => ['required', 'integer', 'gt:0'],
            'calculated_at' => ['required', new Date]
        ];
    }
}
