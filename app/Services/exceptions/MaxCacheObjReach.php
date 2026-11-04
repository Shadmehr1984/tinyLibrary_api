<?php 

namespace App\Services\exceptions;

class MaxCacheObjReach extends \Exception
{
    public function __construct(int $max_cache_obj)
    {
        parent::__construct("reach max cache obj:" . $max_cache_obj);
    }
}

?>