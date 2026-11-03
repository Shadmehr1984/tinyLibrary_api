<?php

namespace App\Repositories\Exceptions;

class ExpireTimeNotInTheRangeException extends \Exception
{
    public function __construct(int $min_expire_time, int $max_expire_time)
    {
        parent::__construct("expire time not in range " . $min_expire_time . " and " . $max_expire_time);
    }
}
