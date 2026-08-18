<?php 

namespace App\Domain\Exceptions;

use Exception;

class NotSetAttributeException extends Exception{
    public function __construct(int $not_set_attributes)
    {
        parent::__construct("all attributes must be set, {$not_set_attributes} attributes is not set");
    }
}

?>