<?php

namespace Tests\Feature\Api\Librarian\Books;

use Illuminate\Support\Facades\Redis;
use Tests\Support\Api\ApiTestCase;
use Tests\Support\Factories\TestDataFactory;
use Tests\Support\Helpers\AuthenticatesApiUsers;

class BookManagementTest extends ApiTestCase
{
    use AuthenticatesApiUsers;

    public function test_librarian_can_add_a_book(): void
    {
        $librarian = TestDataFactory::librarian();
        $category = TestDataFactory::category();

        $this->authenticateAsLibrarian($librarian)
            ->postJson('/v1/librarian/add_book', [
                'title' => 'Distributed Systems',
                'author' => 'Andrew Tanenbaum',
                'isbn' => '9780306406157',
                'published' => '2024-01-15',
                'publisher' => 'Test Press',
                'category_id' => $category->id,
                'total_copies' => 4,
                'description' => 'A detailed book about distributed systems.',
                'location' => 'Shelf A1',
            ])->assertCreated()
            ->assertJsonPath('message', 'book created');

        $this->assertDatabaseHas('books', [
            'isbn' => '9780306406157',
            'category_id' => $category->id,
            'total_copies' => 4,
            'available_copies' => 4,
            'deleted_at' => null,
        ]);
    }

    public function test_add_book_rejects_missing_category_and_invalid_isbn(): void
    {
        $this->authenticateAsLibrarian(TestDataFactory::librarian())
            ->postJson('/v1/librarian/add_book', [
                'title' => 'Invalid Book',
                'author' => 'Test Author',
                'isbn' => 'not-an-isbn',
                'published' => '2024-01-15',
                'publisher' => 'Test Press',
                'category_id' => 99999,
                'total_copies' => 1,
                'description' => 'A sufficiently long description.',
                'location' => 'Shelf A1',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['isbn', 'category_id']);
    }

    public function test_librarian_can_update_a_book(): void
    {
        $librarian = TestDataFactory::librarian();
        $category = TestDataFactory::category();
        $book = TestDataFactory::book($category, ['isbn' => '9780306406157']);

        $this->authenticateAsLibrarian($librarian)
            ->putJson('/v1/librarian/update_book', [
                'target_isbn' => $book->isbn,
                'title' => 'Updated Systems Book',
                'location' => 'Shelf B2',
            ])->assertOk()
            ->assertJsonPath('message', 'book updated');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Updated Systems Book',
            'location' => 'Shelf B2',
        ]);
    }

    public function test_librarian_can_soft_delete_a_book(): void
    {
        $librarian = TestDataFactory::librarian();
        $book = TestDataFactory::book(null, ['isbn' => '9780306406157']);

        $this->authenticateAsLibrarian($librarian)
            ->deleteJson('/v1/librarian/delete_book', ['isbn' => $book->isbn])
            ->assertOk()
            ->assertJsonPath('message', 'book deleted');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
        $this->assertNotNull($book->fresh()->deleted_at);
    }

    public function test_librarian_can_search_books_by_isbn_without_using_real_redis(): void
    {
        $librarian = TestDataFactory::librarian();
        $book = TestDataFactory::book(null, ['isbn' => '9780306406157']);

        Redis::shouldReceive('scan')
            ->once()
            ->andReturn(['0', []]);
        Redis::shouldReceive('exists')
            ->once()
            ->andReturn(0);
        Redis::shouldReceive('dbSize')
            ->once()
            ->andReturn(0);
        Redis::shouldReceive('hSet')
            ->zeroOrMoreTimes()
            ->andReturn(1);
        Redis::shouldReceive('expire')
            ->once()
            ->andReturn(1);

        $this->authenticateAsLibrarian($librarian)
            ->postJson('/v1/librarian/search_book', ['isbn' => $book->isbn])
            ->assertOk()
            ->assertJsonPath('books.0.isbn', $book->isbn);
    }
    public function test_add_book_rejects_an_isbn_already_in_use(): void
    {
        $librarian = TestDataFactory::librarian();
        TestDataFactory::book(null, ['isbn' => '9780306406157']);
        $category = TestDataFactory::category();

        $this->authenticateAsLibrarian($librarian)
            ->postJson('/v1/librarian/add_book', [
                'title' => 'Duplicate ISBN',
                'author' => 'Test Author',
                'isbn' => '9780306406157',
                'published' => '2024-01-15',
                'publisher' => 'Test Press',
                'category_id' => $category->id,
                'total_copies' => 1,
                'description' => 'A sufficiently long description.',
                'location' => 'Shelf A1',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['isbn']);
    }

}
