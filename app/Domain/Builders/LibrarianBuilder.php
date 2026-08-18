<?php

namespace App\Domain\Builders;

use App\Domain\Entities\Librarian;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Phone;

class LibrarianBuilder extends Builder
{
    protected int|null $id;
    protected string $name;
    protected Email $email;
    protected string $password;
    protected Phone $phone;
    protected string $address;

    public function __construct()
    {
        $this->set_attributes_number(6);
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

    public function set_password(string $password){
        $this->password = $this->set_attribute($password, 'password');
        return $this;
    }

    public function set_phone(Phone $phone)
    {
        $this->phone = $this->set_attribute($phone, 'phone');
        return $this;
    }

    public function set_address(string $address)
    {
        $this->address = $this->set_attribute($address, 'address');
        return $this;
    }

    public function build()
    {
        if ($this->check_all_attributes_is_set()) {
            $librarian = new Librarian(
                $this->id,
                $this->name,
                $this->email,
                $this->password,
                $this->phone,
                $this->address
            );

            $this->unset_attributes();

            return $librarian;
        }
    }
}
