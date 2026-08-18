<?php

namespace App\Http\Requests;

use App\Rules\Date;
use App\Rules\Isbn;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
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
            'title' => ['required', Rule::string()],
            'author' => ['required', Rule::string()],
            'isbn' => ['required', new Isbn, Rule::unique('books')],
            'published' => ['required', new Date],
            'publisher' => ['required', Rule::string()],
            'category_id' => ['required', 'integer', 'gt:0', 'exists:categories,id'],
            'total_copies' => ['required', 'integer', 'gt:0'],
            'description' => ['required', Rule::string()->min(10)],
            'location' => ['required', Rule::string()]
        ];
    }
}
