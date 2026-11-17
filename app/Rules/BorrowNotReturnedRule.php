<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;

class BorrowNotReturnedRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $alreadyReturned = DB::table('borrows')
            ->where('id', '=', $value)
            ->where('status', '=', 'returned')
            ->exists();

        if ($alreadyReturned) {
            $fail('book already returned');
        }
    }
}
