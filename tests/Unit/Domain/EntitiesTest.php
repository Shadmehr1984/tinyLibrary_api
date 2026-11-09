<?php

namespace Tests\Unit\Domain;

use App\Domain\Entities\Book;
use App\Domain\Entities\Borrow;
use App\Domain\Entities\Category;
use App\Domain\Entities\Librarian;
use App\Domain\Entities\Member;
use App\Domain\ValueObjects\BorrowStatus;
use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\ISBN;
use App\Domain\ValueObjects\Phone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EntitiesTest extends TestCase
{
    public function test_book_returns_its_attributes_and_preserves_value_objects_by_default(): void
    {
        $published = new Date('2024-05-10');
        $isbn = new ISBN('9780306406157');
        $book = new Book(
            12,
            'The Pragmatic Programmer',
            'Andrew Hunt',
            $isbn,
            $published,
            'Addison-Wesley',
            3,
            5,
            2,
            'A software development book.',
            'A-12',
            null,
        );

        $this->assertSame([
            'id' => 12,
            'title' => 'The Pragmatic Programmer',
            'author' => 'Andrew Hunt',
            'isbn' => $isbn,
            'published' => $published,
            'publisher' => 'Addison-Wesley',
            'category_id' => 3,
            'total_copies' => 5,
            'available_copies' => 2,
            'description' => 'A software development book.',
            'location' => 'A-12',
            'deleted_at' => null,
        ], $book->get());
    }

    public function test_book_returns_scalar_values_when_pure_value_is_enabled(): void
    {
        $published = new Date('2024-05-10');
        $deletedAt = new Date('2025-01-01');
        $book = new Book(
            null,
            'Clean Code',
            'Robert C. Martin',
            new ISBN('9780132350884'),
            $published,
            'Prentice Hall',
            2,
            4,
            4,
            'A handbook of agile software craftsmanship.',
            'B-02',
            $deletedAt,
        );

        $book->pure_value(true);

        $this->assertSame('9780132350884', $book->get()['isbn']);
        $this->assertSame('2024-05-10', $book->get()['published']);
        $this->assertSame('2025-01-01', $book->get()['deleted_at']);
    }

    public function test_category_returns_its_attributes(): void
    {
        $category = new Category(4, 'Programming', 'Books about software development.');

        $this->assertSame([
            'id' => 4,
            'name' => 'Programming',
            'description' => 'Books about software development.',
        ], $category->get());
    }

    public function test_member_returns_value_objects_by_default_and_scalars_when_requested(): void
    {
        $email = new Email('reader@example.com');
        $phone = new Phone('+491234567890');
        $membershipDate = new Date('2025-02-15');
        $member = new Member(
            7,
            'Alex Reader',
            $email,
            'hashed-password',
            $phone,
            'Berlin',
            $membershipDate,
            true,
            0,
        );

        $this->assertSame($email, $member->get()['email']);
        $this->assertSame($phone, $member->get()['phone']);
        $this->assertSame($membershipDate, $member->get()['membership_date']);

        $member->pure_value(true);

        $this->assertSame('reader@example.com', $member->get()['email']);
        $this->assertSame('+491234567890', $member->get()['phone']);
        $this->assertSame('2025-02-15', $member->get()['membership_date']);
    }

    public function test_member_rejects_a_negative_penalty_balance(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Member(
            7,
            'Alex Reader',
            new Email('reader@example.com'),
            'hashed-password',
            new Phone('+491234567890'),
            'Berlin',
            new Date('2025-02-15'),
            true,
            -1,
        );
    }

    public function test_borrow_handles_nullable_dates_and_converts_status_when_pure_value_is_enabled(): void
    {
        $borrowedAt = new Date('2025-03-01');
        $borrow = new Borrow(
            21,
            7,
            12,
            $borrowedAt,
            null,
            null,
            BorrowStatus::borrowed,
            0,
        );

        $result = $borrow->get();

        $this->assertSame($borrowedAt, $result['borrowed_at']);
        $this->assertNull($result['due_date']);
        $this->assertNull($result['returned_at']);
        $this->assertSame(BorrowStatus::borrowed, $result['status']);

        $borrow->pure_value(true);
        $result = $borrow->get();

        $this->assertSame('2025-03-01', $result['borrowed_at']);
        $this->assertSame('borrowed', $result['status']);
    }

    public function test_librarian_converts_email_and_phone_to_scalars_when_pure_value_is_enabled(): void
    {
        $librarian = new Librarian(
            2,
            'Morgan Admin',
            new Email('librarian@example.com'),
            'hashed-password',
            new Phone('+491234567890'),
            'Berlin',
        );

        $this->assertInstanceOf(Email::class, $librarian->get()['email']);
        $this->assertInstanceOf(Phone::class, $librarian->get()['phone']);

        $librarian->pure_value(true);

        $this->assertSame('librarian@example.com', $librarian->get()['email']);
        $this->assertSame('+491234567890', $librarian->get()['phone']);
    }
}
