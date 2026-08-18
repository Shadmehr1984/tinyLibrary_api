<?php 

namespace App\Repositories;

use App\Domain\Builders\BorrowBuilder;
use App\Domain\Entities\Borrow;
use App\Domain\ValueObjects\BorrowStatus;
use App\Models\Borrow as BorrowModel;
use App\Domain\ValueObjects\Date;

class BorrowRepository extends Repository{
    protected static $model_class = BorrowModel::class;
    protected static $builder_class = BorrowBuilder::class;
    protected static $attributes_name = [];
    protected static $attributes_special_type = [
        'borrowed_at' => Date::class,
        'due_date' => Date::class,
        'returned_at' => Date::class,
        'status' => BorrowStatus::class
    ];

    public function __construct(Borrow $borrow)
    {
        parent::__construct($borrow);
    }
}

?>