<?php

namespace App\Repositories\Exceptions;

class KeyIsEmptyException extends \Exception
{
    public function __construct()
    {
        parent::__construct("key is empty");
    }
}
