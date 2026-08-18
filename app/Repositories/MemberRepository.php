<?php 

namespace App\Repositories;

use App\Domain\Builders\MemberBuilder;
use App\Domain\Entities\Member;
use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Phone;
use App\Models\Member as MemberModel;

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
}

?>