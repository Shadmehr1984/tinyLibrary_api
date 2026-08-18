<?php

namespace App\Http\Requests;

use App\Rules\Date;
use App\Rules\Isbn;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookSearchRequest extends FormRequest
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
            'title' => ['nullable', Rule::string()],
            'author' => ['nullable', Rule::string()],
            'isbn' => ['nullable', new Isbn, Rule::unique('books')],
            'published' => ['nullable', new Date],
            'published_before' => ['nullable', new date],
            'published_after' => ['nullable', new date],
            'publisher' => ['nullable', Rule::string()],
            'category_id' => ['nullable', 'integer', 'gt:0', 'exists:categories,id'],
            'total_copies' => ['nullable', 'integer', 'gt:0'],
            'total_copies_lower_than' => ['nullable', 'integer', 'gt:0'],
            'total_copies_greater_than' => ['nullable', 'integer', 'gt:0'],
            'available_copies' => ['nullable', 'integer', 'gt:0'],
            'available_copies_lower_than' => ['nullable', 'integer', 'gt:0'],
            'available_copies_greater_than' => ['nullable', 'integer', 'gt:0'],
            'description' => ['nullable', Rule::string()->min(10)->max(40)],
            'location' => ['nullable', Rule::string()],
            '_check' => [
                'required_without_all:title,author,isbn,published,published_before,published_after,publisher,category_id,total_copies,total_copies_lower_than,total_copies_greater_than,available_copies,available_copies_lower_than,available_copies_greater_than,description,location'
            ]
        ];
    }

    public function prepareForValidation(){
        $this->merge([
            '_check' => 'dummy'
        ]);
    }
}
