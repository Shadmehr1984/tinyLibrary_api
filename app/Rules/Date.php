<?php

namespace App\Rules;

use App\Domain\ValueObjects\Date as ValueObjectsDate;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Date implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            new ValueObjectsDate($value);
        } catch (\Throwable $th) {
            $fail($th->getMessage());
        }
    }
}
