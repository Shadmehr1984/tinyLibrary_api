<?php

namespace App\Rules;

use App\Domain\ValueObjects\Phone as ValueObjectsPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Phone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            new ValueObjectsPhone($value);
        } catch (\Throwable $th) {
            $fail($th->getMessage());
        }
    }
}
