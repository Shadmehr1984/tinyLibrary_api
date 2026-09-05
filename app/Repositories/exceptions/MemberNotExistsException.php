<?php

namespace App\Repositories\Exceptions;

class MemberNotExistsException extends \Exception{
    public function __construct(int $member_id)
    {
        parent::__construct("member_id {$member_id} not exists");
    }
}