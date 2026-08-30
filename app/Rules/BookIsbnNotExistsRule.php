<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;

class BookIsbnNotExistsRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isbn = DB::table('books')->select(['isbn'])->where('isbn', '=', $value)->get();

        if ($isbn){
            $fail('isbn '.$value.' already exist');
        }
    }
}
