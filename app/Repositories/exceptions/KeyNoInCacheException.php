<?php

namespace App\Repositories\Exceptions;

class KeyNoInCacheException extends \Exception
{
    public function __construct(string $key)
    {
        parent::__construct("key " . $key . " is not in cache");
    }
}
