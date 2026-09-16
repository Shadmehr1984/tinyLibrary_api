<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CanNotSetWithOtherFieldRule implements ValidationRule
{
    public function __construct(private mixed $other_field, private string $field_name)
    {
        
    }
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (isset($this->other_field)){
            $fail("{$attribute} can not set with {$this->field_name}");
        }
    }
}
