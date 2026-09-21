<?php

namespace App\Repositories;

use App\Domain\Builders\PenaltyBuilder;
use App\Domain\Entities\penalty;
use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Phone;
use App\Models\Member as MemberModel;
use App\Models\Penalty as PenaltyModel;
use App\Repositories\Exceptions\MemberNotExistsException;
use App\Services\BorrowServices;

class PenaltyRepository extends Repository
{
    protected static $model_class = PenaltyModel::class;
    protected static $builder_class = PenaltyBuilder::class;
    protected static $attributes_name = [];
    protected static $attributes_special_type = [
        'calculated_at' => Date::class,
        'paid_at' => Date::class
    ];

    public function __construct(penalty $penalty)
    {
        parent::__construct($penalty);
    }

    public static function member_have_unpaid_penalty(int $member_id): bool
    {
        $member = MemberModel::where('id', '=', $member_id)->get();

        if (sizeof($member) == 0) {
            throw new MemberNotExistsException($member_id);
        }

        $penalties = PenaltyModel::where('member_id', '=', $member_id)->where('paid_at', '=', null)->get();

        return sizeof($penalties) > 0;
    }

    public static function penalties_exists($delayed_borrows)
    {
        $penalties = PenaltyModel::all();

        foreach ($delayed_borrows as &$delayed_borrow) {
            $delayed_borrow['exists'] = null;
            foreach ($penalties as $penalty) {
                if ($delayed_borrow['borrowed_id'] == $penalty->borrowed_id && $delayed_borrow['member_id'] == $penalty->member_id){
                    $delayed_borrow['exists'] = true;
                    break;
                }
            }
            if ($delayed_borrow['exists'] == null){
                $delayed_borrow['exists'] = false;
            }
        }

        return $delayed_borrows;
    }

    public static function update_penalties_detail()
    {
        $penalties = PenaltyModel::all();

        $now = Date::now();

        $updated = 0;

        foreach ($penalties as $penalty ){
            $different = Date::difference($now, new Date($penalty->calculated_at));
            if ($different > 0){
                $penalty->calculated_at = $now->__toString();
                $penalty->amount = $penalty->amount + ($different * BorrowServices::PENALTY_AMOUNT_PER_DAY);
                $penalty->save();
                $updated++;
            }
        }

        return ['updated_penalty' => $updated];
    }
}
