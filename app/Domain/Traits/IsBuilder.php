<?php 


namespace App\Domain\Traits;

use App\Domain\Exceptions\NotSetAttributeException;

trait IsBuilder{
    private int $attributes_number;
    private array $is_set_attributes = [];

    protected function check_all_attributes_is_set(): bool{
        if ($this->attributes_number > 0){
            throw new NotSetAttributeException($this->attributes_number);
        }
        return true;
    }

    protected function set_attribute($value, string $name): mixed{
        if (!in_array($name, $this->is_set_attributes)){
            $this->attributes_number -= 1;
            $this->is_set_attributes[] = $name;
        }
        return $value;
    }

    protected function set_attributes_number(int $attributes_number){
        $this->attributes_number = $attributes_number;
    }

    protected function unset_attributes(){
        foreach ($this->is_set_attributes as $attribute) {
            unset($this->$attribute);
        }
    }
}
?>