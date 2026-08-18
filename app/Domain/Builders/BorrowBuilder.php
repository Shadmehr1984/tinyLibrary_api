<?php

namespace App\Domain\Builders;

use App\Domain\Entities\Borrow;
use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\BorrowStatus;

class BorrowBuilder extends Builder
{
    protected int|null $id;
    protected int $member_id;
    protected int $book_id;
    protected Date $borrowed_at;
    protected Date|null $due_date;
    protected Date|null $returned_at;
    protected BorrowStatus $status;
    protected int $penalty_amount;

    public function __construct()
    {
        $this->set_attributes_number(8);
    }

    public function set_id(int|null $id)
    {
        $this->id = $this->set_attribute($id, 'id');
        return $this;
    }

    public function set_member_id(int $member_id)
    {
        $this->member_id = $this->set_attribute($member_id, 'member_id');
        return $this;
    }

    public function set_book_id(int $book_id)
    {
        $this->book_id = $this->set_attribute($book_id, 'book_id');
        return $this;
    }

    public function set_borrowed_at(date $borrowed_at)
    {
        $this->borrowed_at = $this->set_attribute($borrowed_at, 'borrowed_at');
        return $this;
    }

    public function set_due_date(Date|null $due_date)
    {
        $this->due_date = $this->set_attribute($due_date, 'due_date');
        return $this;
    }

    public function set_returned_at(Date|null $returned_at)
    {
        $this->returned_at = $this->set_attribute($returned_at, 'returned_at');
        return $this;
    }

    public function set_status(BorrowStatus $status)
    {
        $this->status = $this->set_attribute($status, 'status');
        return $this;
    }

    public function set_penalty_amount(int $penalty_amount)
    {
        $this->penalty_amount = $this->set_attribute($penalty_amount, 'penalty_amount');
        return $this;
    }

    public function build()
    {
        if ($this->check_all_attributes_is_set()) {
            $borrow = new Borrow(
                $this->id,
                $this->member_id,
                $this->book_id,
                $this->borrowed_at,
                $this->due_date,
                $this->returned_at,
                $this->status,
                $this->penalty_amount
            );

            $this->unset_attributes();

            return $borrow;
        }
    }
}
