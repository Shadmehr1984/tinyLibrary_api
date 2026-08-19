<?php

namespace App\Http\Requests;

use App\Rules\Date;
use App\Rules\Isbn;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookUpdateRequest extends FormRequest
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
            'target_isbn' => ['required', new Isbn, 'exists:books'],
            'title' => ['nullable', 'string'],
            'author' => ['nullable', 'string'],
            'isbn' => ['nullable', new Isbn, Rule::unique('books')],
            'published' => ['nullable', new Date],
            'publisher' => ['nullable', Rule::string()],
            'category_id' => ['nullable', 'integer', 'gt:0', 'exists:categories,id'],
            'total_copies' => ['nullable', 'integer', 'gt:0'],
            'description' => ['nullable', Rule::string()->min(10)],
            'location' => ['nullable', Rule::string()],
        ];
    }
}
