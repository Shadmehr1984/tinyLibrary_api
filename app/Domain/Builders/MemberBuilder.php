<?php

namespace App\Domain\Builders;

use App\Domain\Entities\Member;
use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Phone;

class MemberBuilder extends Builder
{
    protected int|null $id;
    protected string $name;
    protected Email $email;
    protected string $password;
    protected Phone $phone;
    protected string $address;
    protected Date $membership_date;
    protected bool $active;
    protected int $penalty_balance;

    public function __construct()
    {
        $this->set_attributes_number(8);
    }

    public function set_id(int|null $id)
    {
        $this->id = $this->set_attribute($id, 'id');
        return $this;
    }

    public function set_name(string $name)
    {
        $this->name = $this->set_attribute($name, 'name');
        return $this;
    }

    public function set_email(Email $email)
    {
        $this->email = $this->set_attribute($email, 'email');
        return $this;
    }

    public function set_password(string $password)
    {
        $this->password = $this->set_attribute($password, 'password');
        return $this;
    }

    public function set_address(string $address)
    {
        $this->address = $this->set_attribute($address, 'address');
        return $this;
    }

    public function set_membership_date(Date $membership_date)
    {
        $this->membership_date = $this->set_attribute($membership_date, 'membership_date');
        return $this;
    }

    public function set_phone(Phone $phone)
    {
        $this->phone = $this->set_attribute($phone, 'phone');
        return $this;
    }

    public function set_active(bool $active)
    {
        $this->active = $this->set_attribute($active, 'active');
        return $this;
    }

    public function set_penalty_balance(bool $penalty_balance)
    {
        $this->penalty_balance = $this->set_attribute($penalty_balance, 'penalty_balance');
        return $this;
    }

    public function build()
    {
        if ($this->check_all_attributes_is_set()) {
            $member = new Member(
                $this->id,
                $this->name,
                $this->email,
                $this->password,
                $this->phone,
                $this->address,
                $this->membership_date,
                $this->active,
                $this->penalty_balance
            );

            $this->unset_attributes();

            return $member;
        }
    }
}
