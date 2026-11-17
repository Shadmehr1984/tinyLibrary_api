<?php

namespace Tests\Support\Factories;

use App\Models\Book;
use App\Models\Borrow;
use App\Models\Category;
use App\Models\Librarian;
use App\Models\Member;
use App\Models\Penalty;

final class TestDataFactory
{
    public static function category(array $attributes = []): Category
    {
        return Category::factory()->create(array_merge([
            'name' => 'API Test Category',
            'description' => 'Category created for API feature tests.',
        ], $attributes));
    }

    public static function book(?Category $category = null, array $attributes = []): Book
    {
        $category ??= static::category();

        return Book::factory()->create(array_merge([
            'category_id' => $category->id,
            'isbn' => '9780000000001',
            'title' => 'API Test Book',
            'author' => 'Test Author',
            'published' => '2024-01-15',
            'publisher' => 'Test Publisher',
            'total_copies' => 3,
            'available_copies' => 3,
            'description' => 'A book record created for API feature tests.',
            'location' => 'Test Shelf',
            'deleted_at' => null,
        ], $attributes));
    }

    public static function member(array $attributes = []): Member
    {
        return Member::factory()->create(array_merge([
            'name' => 'API Test Member',
            'email' => 'member@example.test',
            'password' => bcrypt('password-123'),
            'phone' => '+10000000000',
            'address' => 'Test Address',
            'membership_date' => '2024-01-01',
            'active' => true,
            'penalty_balance' => 0,
        ], $attributes));
    }

    public static function librarian(array $attributes = []): Librarian
    {
        return Librarian::factory()->create(array_merge([
            'name' => 'API Test Librarian',
            'email' => 'librarian@example.test',
            'password' => bcrypt('password-123'),
            'phone' => '+10000000001',
            'address' => 'Test Office',
        ], $attributes));
    }

    public static function borrow(Member $member, Book $book, array $attributes = []): Borrow
    {
        return Borrow::factory()->create(array_merge([
            'member_id' => $member->id,
            'book_id' => $book->id,
            'borrowed_at' => '2025-01-01',
            'due_date' => '2025-01-15',
            'returned_at' => null,
            'status' => 'borrowed',
            'penalty_amount' => 0,
        ], $attributes));
    }

    public static function penalty(Member $member, Borrow $borrow, array $attributes = []): Penalty
    {
        return Penalty::factory()->create(array_merge([
            'member_id' => $member->id,
            'borrowed_id' => $borrow->id,
            'amount' => 1000,
            'calculated_at' => '2025-01-16',
            'paid_at' => null,
        ], $attributes));
    }
}
