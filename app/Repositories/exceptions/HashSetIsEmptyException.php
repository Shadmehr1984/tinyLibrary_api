<?php

namespace App\Repositories\Exceptions;

class HashSetIsEmptyException extends \Exception
{
    public function __construct()
    {
        parent::__construct("hash set is empty");
    }
}
