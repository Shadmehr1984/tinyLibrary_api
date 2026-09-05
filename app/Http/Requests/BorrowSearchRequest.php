<?php

namespace App\Http\Requests;

use App\Rules\Date;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BorrowSearchRequest extends FormRequest
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
            'member_id' => ['nullable', 'integer', 'gt:0'],
            'book_id' => ['nullable', 'integer', 'gt:0'],
            'borrowed_at' => ['nullable', new Date],
            'borrowed_before_at' => ['nullable', new Date],
            'borrowed_after_at' => ['nullable', new Date],
            'due_date' => ['nullable', new Date],
            'due_date_before' => ['nullable', new Date],
            'due_date_after' => ['nullable', new Date],
            'returned_at' => ['nullable', new Date],
            'returned_before_at' => ['nullable', new Date],
            'returned_after_at' => ['nullable', new Date],
            'status' => ['nullable', Rule::in(['borrowed', 'returned', 'overdue'])],
            'penalty_amount' => ['nullable', 'integer', 'gt:0'],
            'penalty_amount_lower_than' => ['nullable', 'integer', 'gt:0'],
            'penalty_amount_greater_than' => ['nullable', 'integer', 'gt:0'],
            'limit' => ['nullable', 'integer', 'gt:0'],
            '_check' => [
                'required_without_all:member_id,book_id,borrowed_at,borrowed_before_at,borrowed_after_at,due_date,'.
                'due_date_before,returned_at,returned_before_at,returned_after_at,status,penalty_amount,penalty_amount_lower_than'.
                'penalty_amount_greater_than'
            ]
        ];
    }

    public function prepareForValidation(){
        $this->merge([
            '_check' => 'dummy'
        ]);
    }
}
