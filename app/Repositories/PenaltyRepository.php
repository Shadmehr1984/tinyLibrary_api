<?php 

namespace App\Repositories;

use App\Domain\Builders\PenaltyBuilder;
use App\Domain\Entities\penalty;
use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Phone;
use App\Models\Penalty as PenaltyModel;

class PenaltyRepository extends Repository{
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
}

?>