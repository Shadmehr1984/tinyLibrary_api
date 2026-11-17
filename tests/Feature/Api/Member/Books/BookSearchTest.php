<?php

namespace Tests\Feature\Api\Member\Books;

use Illuminate\Support\Facades\Redis;
use Tests\Support\Api\ApiTestCase;
use Tests\Support\Factories\TestDataFactory;
use Tests\Support\Helpers\AuthenticatesApiUsers;

class BookSearchTest extends ApiTestCase
{
    use AuthenticatesApiUsers;

    public function test_active_member_can_search_books_by_isbn_without_real_redis(): void
    {
        $member = TestDataFactory::member();
        $book = TestDataFactory::book(null, ['isbn' => '9780306406157']);

        Redis::shouldReceive('scan')->once()->andReturn(['0', []]);
        Redis::shouldReceive('exists')->once()->andReturn(0);
        Redis::shouldReceive('dbSize')->once()->andReturn(0);
        Redis::shouldReceive('hSet')->zeroOrMoreTimes()->andReturn(1);
        Redis::shouldReceive('expire')->once()->andReturn(1);

        $this->authenticateAsMember($member)
            ->postJson('/api/v1/member/search_book', ['isbn' => $book->isbn])
            ->assertOk()
            ->assertJsonPath('books.0.isbn', $book->isbn);
    }

    public function test_inactive_member_cannot_search_books(): void
    {
        $member = TestDataFactory::member(['active' => false]);

        $this->authenticateAsMember($member)
            ->postJson('/api/v1/member/search_book', ['title' => 'anything'])
            ->assertForbidden();
    }
}
