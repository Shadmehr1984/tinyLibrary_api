<?php

namespace Tests\Unit\Domain;

use App\Domain\ValueObjects\BorrowStatus;
use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\ISBN;
use App\Domain\ValueObjects\Phone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValueObjectsTest extends TestCase
{
    public function test_date_stores_and_returns_a_valid_formatted_date(): void
    {
        $date = new Date('2026-11-10');

        $this->assertSame('2026-11-10', $date->get());
        $this->assertSame('2026-11-10', (string) $date);
    }

    public function test_date_can_be_updated_after_construction(): void
    {
        $date = new Date('2026-01-01');

        $date->set('2026-12-31');

        $this->assertSame('2026-12-31', $date->get());
    }

    public function test_date_rejects_a_value_with_an_invalid_length(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Date('2026-1-1');
    }

    public function test_date_rejects_non_numeric_date_components(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Date('202A-11-10');
    }

    public function test_date_rejects_missing_hyphens(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Date('2026/11/10');
    }

    public function test_date_difference_returns_the_number_of_days_between_two_dates(): void
    {
        $first = new Date('2026-11-01');
        $second = new Date('2026-11-10');

        $this->assertSame(9, Date::difference($first, $second));
    }

    public function test_date_now_returns_a_date_in_year_month_day_format(): void
    {
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}$/',
            Date::now()->get(),
        );
    }

    public function test_isbn_stores_and_returns_a_thirteen_digit_value(): void
    {
        $isbn = new ISBN('9780306406157');

        $this->assertSame('9780306406157', $isbn->get());
        $this->assertSame('9780306406157', (string) $isbn);
    }

    public function test_isbn_can_be_updated_after_construction(): void
    {
        $isbn = new ISBN('9780306406157');

        $isbn->set('9780132350884');

        $this->assertSame('9780132350884', $isbn->get());
    }

    public function test_isbn_rejects_a_value_that_is_not_thirteen_characters_long(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ISBN('123456789012');
    }

    public function test_isbn_rejects_non_numeric_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ISBN('97803064061A7');
    }

    public function test_email_stores_and_returns_a_valid_email_address(): void
    {
        $email = new Email('reader@example.com');

        $this->assertSame('reader@example.com', $email->get());
        $this->assertSame('reader@example.com', (string) $email);
    }

    public function test_email_can_be_updated_after_construction(): void
    {
        $email = new Email('old@example.com');

        $email->set('new@example.com');

        $this->assertSame('new@example.com', $email->get());
    }

    public function test_email_rejects_an_invalid_email_address(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('not-an-email');
    }

    public function test_phone_stores_and_returns_a_valid_international_number(): void
    {
        $phone = new Phone('+491234567890');

        $this->assertSame('+491234567890', $phone->get());
        $this->assertSame('+491234567890', (string) $phone);
    }

    public function test_phone_can_be_updated_after_construction(): void
    {
        $phone = new Phone('+491234567890');

        $phone->set('+441234567890');

        $this->assertSame('+441234567890', $phone->get());
    }

    public function test_phone_rejects_a_number_shorter_than_the_minimum_length(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Phone('+1234567890');
    }

    public function test_phone_rejects_a_number_that_does_not_start_with_plus(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Phone('491234567890');
    }

    public function test_phone_rejects_non_numeric_characters_after_the_plus_sign(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Phone('+49123456789A');
    }

    public function test_borrow_status_exposes_all_supported_backed_values(): void
    {
        $this->assertSame('borrowed', BorrowStatus::borrowed->value);
        $this->assertSame('returned', BorrowStatus::returned->value);
        $this->assertSame('overdue', BorrowStatus::overdue->value);
    }

    public function test_borrow_status_can_be_created_from_a_backed_value(): void
    {
        $this->assertSame(BorrowStatus::returned, BorrowStatus::from('returned'));
    }
}
