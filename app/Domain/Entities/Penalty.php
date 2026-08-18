<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Date;

class penalty extends Entity
{
    public function __construct(
        protected int|null $id,
        protected int $borrowed_id,
        protected int $member_id,
        protected int $amount,
        protected Date $calculated_at,
        protected Date|null $paid_at
    ) {
        if ($this->amount < 0) {
            throw new \InvalidArgumentException('amount is positive');
        }
    }

    public function get()
    {
        return [
            'id' => $this->id,
            'borrowed_id' => $this->borrowed_id,
            'member_id' => $this->member_id,
            'amount' => $this->amount,
            'calculated_at' => $this->calculated_at,
            'paid_at' => $this->paid_at
        ];
    }
}
