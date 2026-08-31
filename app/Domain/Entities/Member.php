<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Phone;

class Member extends Entity
{
    public function __construct(
        protected int|null $id,
        protected string $name,
        protected Email $email,
        protected string $password,
        protected Phone $phone,
        protected string $address,
        protected Date $membership_date,
        protected bool $active,
        protected int $penalty_balance
    ) {
        if ($this->penalty_balance < 0) {
            throw new \InvalidArgumentException('penalty balance is positive');
        }
    }

    public function get()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->pure_value ? $this->email->get() : $this->email,
            'password' => $this->password,
            'phone' => $this->pure_value ? $this->phone->get() : $this->phone,
            'address' => $this->address,
            'membership_date' => $this->pure_value ? $this->membership_date->get() : $this->membership_date,
            'active' => $this->active,
            'penalty_balance' => $this->penalty_balance
        ];
    }
}
