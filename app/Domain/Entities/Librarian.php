<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Phone;

class Librarian extends Entity
{
    public function __construct(
        protected int|null $id,
        protected string $name,
        protected Email $email,
        protected string $password,
        protected Phone $phone,
        protected string $address
    ) {
    }

    public function get()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'phone' => $this->phone,
            'address' => $this->address
        ];
    }
}
