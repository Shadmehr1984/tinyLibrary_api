<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\BorrowStatus;
use App\Domain\ValueObjects\Date;

class Borrow extends Entity
{
    public function __construct(
        protected int|null $id,
        protected int $member_id,
        protected int $book_id,
        protected Date $borrowed_at,
        protected Date|null $due_date,
        protected Date|null $returned_at,
        protected BorrowStatus $status,
        protected int $penalty_amount
    ) {
    }

    public function get()
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'book_id' => $this->book_id,
            'borrowed_at' => $this->borrowed_at,
            'due_date' => $this->due_date,
            'returned_at' => $this->returned_at,
            'status' => $this->status,
            'penalty_amount' => $this->penalty_amount
        ];
    }
}
