<?php 

namespace App\Services;

class Service{
    protected static function fix_str_like_op(string $str){
        return '%'.str_replace(' ', '%', $str).'%';
    }
}

?>