<?php

namespace App\Http\Requests;

use App\Rules\Date;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BorrowUpdateRequest extends FormRequest
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
            'target_book_id' => ['required', 'integer', 'gt:0'],
            'target_borrowed_at' => [
                'required',
                new Date,
                Rule::exists('borrows', 'borrowed_at')->where('member_id', $this->target_member_id)->where('book_id', $this->target_book_id)
            ],
            'due_date' => ['nullable', new Date],
            'returned_at' => ['nullable', new Date],
            'status' => ['nullable', Rule::in(['borrowed', 'returned', 'overdue'])],
            'penalty_amount' => ['nullable', 'integer', 'gt:0']
        ];
    }
}
