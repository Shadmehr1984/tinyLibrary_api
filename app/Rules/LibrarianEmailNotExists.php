<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;

class LibrarianEmailNotExists implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $emailExists = DB::table('librarians')
            ->where('email', '=', $value)
            ->exists();

        if ($emailExists) {
            $fail('email '.$value.' already exists');
        }
    }
}
