<?php 

namespace App\Domain\Entities;

use App\Domain\Traits\HasDynamicUpdate;

class Entity{
    use HasDynamicUpdate;

    protected bool $pure_value = false;

    public function pure_value(bool $pure_value){
        $this->pure_value = $pure_value;
    }

    public function get(){}

}

?>