<?php 

namespace App\Domain\ValueObjects;

enum BorrowStatus: string{
    case borrowed = 'borrowed';
    case returned = 'returned';
    case overdue = 'overdue';
}

?>