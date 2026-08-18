<?php 

namespace App\Domain\ValueObjects;

class Date{
    const DATE_RIGHT_SIZE = 10;
    const DATE_NUMBERS_INDEXES = [0, 1, 2, 3, 5, 6, 8, 9];
    const DATE_HYPHENS_INDEXES = [4, 7];

    private string $date;

    public function __construct(string $date)
    {
        $this->set($date);
    }

    public static function validate_date(string $date){
        if (strlen($date) != static::DATE_RIGHT_SIZE){
            throw new \InvalidArgumentException('date size must be 10');
        }

        $date_characters = str_split($date);

        //validate numeric characters
        foreach (static::DATE_NUMBERS_INDEXES as $number_index) {
            if (!is_numeric($date_characters[$number_index])){
                throw new \InvalidArgumentException("character {$number_index}th must be number");
            }
        }

        //validate slashes
        foreach (static::DATE_HYPHENS_INDEXES as $hyphen_index) {
            if ($date_characters[$hyphen_index] != '-'){
                throw new \InvalidArgumentException("character {$hyphen_index}th must be hyphen(-)");
            }
        }
    }

    public function get(){
        return $this->date;
    }

    public function set(string $date){
        static::validate_date($date);
        $this->date = $date;
    }

    public static function now(){
        $now = date("Y-m-d");
        return new static($now);
    }

    public function __toString()
    {
        return $this->date;
    }
}

?>