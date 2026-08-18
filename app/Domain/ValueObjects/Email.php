<?php 

namespace App\Domain\ValueObjects;

class Email{
    private string $email;

    public function __construct(string $email){
        $this->set($email);
    }

    public static function validate_email(string $email){
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            throw new \InvalidArgumentException('invalid email');
        }
    }

    public function get(){
        return $this->email;
    }

    public function set(string $email){
        static::validate_email($email);
        $this->email = $email;
    }

    public function __toString()
    {
        return $this->email;
    }
}

?>