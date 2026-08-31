<?php 

namespace App\Domain\ValueObjects;

class Phone{
    const MINIMUM_PHONE_NUMBER_SIZE = 12;

    private string $phone;

    public function __construct(string $phone)
    {
        $this->set($phone);
    }

    public static function validate_phone(string $phone){
        $phone_size = strlen($phone);

        if ($phone_size < static::MINIMUM_PHONE_NUMBER_SIZE){
            throw new \InvalidArgumentException('phone must have at least 12 characters');
        }

        $phone_characters = str_split($phone);

        if ($phone_characters[0] != '+'){
            throw new \InvalidArgumentException("phone should start with +, value:{$phone}");
        }

        for ($i=1; $i < $phone_size; $i++) { 
            if (!is_numeric($phone_characters[$i])){
                throw new \InvalidArgumentException("phone should contains numbers and + sign, character {$i}th is'nt a number");
            }
        }
    }

    public function get(){
        return $this->phone;
    }

    public function set(string $phone){
        $this->validate_phone($phone);
        $this->phone = $phone;
    }

    public function __toString()
    {
        return $this->phone;
    }
}

?>