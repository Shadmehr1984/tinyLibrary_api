<?php

namespace App\Repositories;

use App\Domain\Builders\BorrowBuilder;
use App\Domain\Entities\Borrow;
use App\Domain\ValueObjects\BorrowStatus;
use App\Models\Borrow as BorrowModel;
use App\Domain\ValueObjects\Date;
use App\Services\BorrowServices;

class BorrowRepository extends Repository
{
    protected static $model_class = BorrowModel::class;
    protected static $builder_class = BorrowBuilder::class;
    protected static $attributes_name = [];
    protected static $attributes_special_type = [
        'borrowed_at' => Date::class,
        'due_date' => Date::class,
        'returned_at' => Date::class,
        'status' => BorrowStatus::class
    ];

    /**
     * @param = Borrow
     */
    public function __construct(Borrow $borrow)
    {
        parent::__construct($borrow);
    }

    /**
     * @return array
     */
    public static function delayed_borrows()
    {
        $borrows = BorrowModel::select(['id', 'member_id', 'borrowed_at'])->get();

        $delayed_borrows = [];

        $now = Date::now();
        foreach ($borrows as $borrow) {
            if (!$borrow->returned_at) {
                $borrowed_at = new Date($borrow->borrowed_at);
                $different = Date::difference($borrowed_at, $now);
                if ($different > BorrowServices::MAX_DELAY_DAYS) {
                    $amount = $different * BorrowServices::PENALTY_AMOUNT_PER_DAY;
                    $delayed_borrows[] = [
                        'borrowed_id' => $borrow->id,
                        'member_id' => $borrow->member_id,
                        'calculated_at' => $now,
                        'amount' => $amount
                    ];
                }
            }
        }

        return $delayed_borrows;
    }
}
