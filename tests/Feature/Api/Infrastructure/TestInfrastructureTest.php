<?php

namespace Tests\Feature\Api\Infrastructure;

use Illuminate\Support\Facades\Redis;
use Tests\Support\Api\ApiTestCase;
use Tests\Support\Factories\TestDataFactory;
use Tests\Support\Helpers\AuthenticatesApiUsers;

class TestInfrastructureTest extends ApiTestCase
{
    use AuthenticatesApiUsers;

    public function test_test_data_factory_creates_related_domain_records(): void
    {
        $category = TestDataFactory::category();
        $book = TestDataFactory::book($category);
        $member = TestDataFactory::member();
        $borrow = TestDataFactory::borrow($member, $book);
        $penalty = TestDataFactory::penalty($member, $borrow);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('members', ['id' => $member->id]);
        $this->assertDatabaseHas('borrows', [
            'id' => $borrow->id,
            'member_id' => $member->id,
            'book_id' => $book->id,
        ]);
        $this->assertDatabaseHas('penalties', [
            'id' => $penalty->id,
            'borrowed_id' => $borrow->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_authentication_helpers_issue_member_and_librarian_tokens(): void
    {
        $member = TestDataFactory::member();
        $librarian = TestDataFactory::librarian();

        $this->assertNotEmpty($this->memberToken($member));
        $this->assertNotEmpty($this->librarianToken($librarian));
    }

    public function test_redis_facade_is_mocked_in_api_test_base_case(): void
    {
        Redis::shouldReceive('exists')
            ->once()
            ->with('api-test-key')
            ->andReturn(0);

        $this->assertSame(0, Redis::exists('api-test-key'));
    }
}
