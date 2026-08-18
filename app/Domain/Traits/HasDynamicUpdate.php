<?php 

namespace App\Domain\Traits;

trait HasDynamicUpdate{
    public function set(array $updates){
        foreach ($updates as $key => $value) {
            $this->$key = $value;
        }
    }
}

?>