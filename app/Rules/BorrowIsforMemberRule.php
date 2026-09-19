<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;

class BorrowIsforMemberRule implements ValidationRule
{
    public function __construct(private int $member_id)
    {
        
    }
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $borrow = DB::table('borrows')->select()->where('id', '=', $value)->where('member_id', '=', $this->member_id)->get();
        if (empty($borrow)){
            $fail('invalid borrow');
        }
    }
}
