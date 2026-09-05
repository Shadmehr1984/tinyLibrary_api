<?php

namespace App\Rules;

use App\Domain\ValueObjects\Date;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;

class DuplicateBorrowRule implements ValidationRule
{

    public function __construct(private int $member_id, private int $book_id)
    {
        
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $borrow = DB::table('borrows')->select()->where('member_id', '=', $this->member_id)->where('book_id', '=', $this->book_id)->where('borrowed_at', '=', Date::now())->get();
        
        if (sizeof($borrow) != 0){
            $fail("duplicate borrow");
        }
    }
}
