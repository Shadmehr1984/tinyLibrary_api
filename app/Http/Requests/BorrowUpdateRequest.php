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
            'member_id' => ['required', 'integer', 'gt:0'],
            'book_id' => ['required', 'integer', 'gt:0'],
            'borrowed_at' => [
                'required',
                new Date,
                Rule::exists('borrows')->where('member_id', $this->member_id)->where('book_id', $this->book_id)
            ],
            'due_date' => ['nullable', new Date],
            'returned_at' => ['nullable', new Date],
            'status' => ['nullable', Rule::in(['borrowed', 'returned', 'overdue'])],
            'penalty_amount' => ['nullable', 'integer', 'gt:0']
        ];
    }
}
