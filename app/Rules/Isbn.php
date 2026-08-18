<?php

namespace App\Rules;

use App\Domain\ValueObjects\ISBN as ValueObjectsISBN;
use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Isbn implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try{
            new ValueObjectsISBN($value);
        }
        catch(Exception $exception){
            $fail($exception->getMessage());
        }
    }
}
