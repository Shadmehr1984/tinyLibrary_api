<?php

namespace App\Http\Requests;

use App\Rules\Date;
use App\Rules\DuplicateBorrowRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BorrowRequest extends FormRequest
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
            'member_id' => ['required', 'integer', 'gt:0', 'exists:members,id'],
            'book_id' => ['required', 'integer', 'gt:0', 'exists:books,id'],
            '_borrow_rule' => [new DuplicateBorrowRule($this->member_id, $this->book_id)]
        ];
    }

    public function prepareForValidation(){
        $this->merge([
            '_borrow_rule' => 'dummy'
        ]);
    }
}
