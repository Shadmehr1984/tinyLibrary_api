<?php 

namespace App\Repositories;

use App\Domain\Builders\MemberBuilder;
use App\Domain\Entities\Member;
use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Phone;
use App\Models\Borrow as BorrowModel;
use App\Models\Member as MemberModel;
use App\Repositories\Exceptions\MemberNotExistsException;

class MemberRepository extends Repository{
    protected static $model_class = MemberModel::class;
    protected static $builder_class = MemberBuilder::class;
    protected static $attributes_name = [];
    protected static $attributes_special_type = [
        'email' => Email::class,
        'phone' => Phone::class,
        'membership_date' => Date::class
    ];

    public function __construct(Member $member)
    {
        parent::__construct($member);
    }

    public static function member_borrows(int $member_id){
        $member = MemberModel::where('id', '=', $member_id)->get();
        if (sizeof($member) == 0){
            throw new MemberNotExistsException($member_id);
        }
        return BorrowModel::where('member_id', '=', $member_id)->count();
    }
}

?>