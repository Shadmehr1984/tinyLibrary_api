<?php

namespace App\Domain\Builders;

use App\Domain\Entities\penalty;
use App\Domain\ValueObjects\Date;

class PenaltyBuilder extends Builder
{
    protected int|null $id;
    protected int $borrowed_id;
    protected int $member_id;
    protected int $amount;
    protected Date $calculated_at;
    protected Date|null $paid_at;

    public function __construct()
    {
        $this->set_attributes_number(6);
    }

    public function set_id(int|null $id)
    {
        $this->id = $this->set_attribute($id, 'id');
        return $this;
    }

    public function set_borrowed_id(int $borrowed_id)
    {
        $this->borrowed_id = $this->set_attribute($borrowed_id, 'borrowed_id');
        return $this;
    }

    public function set_member_id(int $member_id)
    {
        $this->member_id = $this->set_attribute($member_id, 'member_id');
        return $this;
    }

    public function set_amount(int $amount)
    {
        $this->amount = $this->set_attribute($amount, 'amount');
        return $this;
    }

    public function set_calculated_at(Date $calculated_at)
    {
        $this->calculated_at = $this->set_attribute($calculated_at, 'calculated_at');
        return $this;
    }

    public function set_paid_at(Date|null $paid_at)
    {
        $this->paid_at = $this->set_attribute($paid_at, 'paid_at');
        return $this;
    }

    public function build()
    {
        if ($this->check_all_attributes_is_set()) {
            $penalty = new penalty(
                $this->id,
                $this->borrowed_id,
                $this->member_id,
                $this->amount,
                $this->calculated_at,
                $this->paid_at
            );

            $this->unset_attributes();

            return $penalty;
        }
    }
}
