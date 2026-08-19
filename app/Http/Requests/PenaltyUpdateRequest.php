<?php

namespace App\Http\Requests;

use App\Rules\Date;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PenaltyUpdateRequest extends FormRequest
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
            'target_member_id' => ['required', 'integer', 'gt:0'],
            'target_borrowed_id' => [
                'required',
                'integer', 'gt:0',
                Rule::exists('penalties', 'borrowed_id')->where('member_id', $this->target_member_id)
            ],
            'paid_at' => ['nullable', new Date]
        ];
    }
}
