<?php

namespace Tests\Feature\Api\Member\Borrows;

use Tests\Support\Api\ApiTestCase;
use Tests\Support\Factories\TestDataFactory;
use Tests\Support\Helpers\AuthenticatesApiUsers;

class BorrowManagementTest extends ApiTestCase
{
    use AuthenticatesApiUsers;

    public function test_member_can_borrow_an_available_book_and_inventory_is_decremented(): void
    {
        $member = TestDataFactory::member();
        $otherMember = TestDataFactory::member(['email' => 'other-member@example.test']);
        $book = TestDataFactory::book(null, [
            'isbn' => '9780306406157',
            'total_copies' => 2,
            'available_copies' => 2,
        ]);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/add_borrow', [
                'book_id' => $book->id,
                'member_id' => $otherMember->id,
            ])->assertCreated()
            ->assertJsonPath('message', 'borrow book successful');

        $this->assertDatabaseHas('borrows', [
            'member_id' => $member->id,
            'book_id' => $book->id,
            'status' => 'borrowed',
        ]);
        $this->assertSame(1, $book->fresh()->available_copies);
    }

    public function test_member_cannot_borrow_when_an_unpaid_penalty_exists(): void
    {
        $member = TestDataFactory::member();
        $book = TestDataFactory::book();
        $borrow = TestDataFactory::borrow($member, $book);
        TestDataFactory::penalty($member, $borrow);

        $newBook = TestDataFactory::book(null, ['isbn' => '9780306406157']);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/add_borrow', ['book_id' => $newBook->id])
            ->assertForbidden()
            ->assertJsonPath('message', 'you have unpaid penalties, first paid your penalties');
    }

    public function test_member_cannot_borrow_more_than_the_active_borrow_limit(): void
    {
        $member = TestDataFactory::member();

        foreach ([
            ['9780306406157', 1],
            ['9780306406164', 2],
            ['9780306406171', 3],
        ] as [$isbn, $number]) {
            $book = TestDataFactory::book(null, ['isbn' => $isbn]);
            TestDataFactory::borrow($member, $book, ['borrowed_at' => '2025-01-0'.$number]);
        }

        $fourthBook = TestDataFactory::book(null, ['isbn' => '9780306406188']);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/add_borrow', ['book_id' => $fourthBook->id])
            ->assertStatus(409)
            ->assertJsonPath('message', 'you reach max borrows limit');
    }

    public function test_member_cannot_borrow_an_unavailable_book(): void
    {
        $member = TestDataFactory::member();
        $book = TestDataFactory::book(null, [
            'isbn' => '9780306406157',
            'total_copies' => 1,
            'available_copies' => 0,
        ]);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/add_borrow', ['book_id' => $book->id])
            ->assertStatus(409)
            ->assertJsonPath('message', 'book is not available');
    }

    public function test_member_borrow_search_is_scoped_to_authenticated_member(): void
    {
        $member = TestDataFactory::member();
        $otherMember = TestDataFactory::member(['email' => 'other-search@example.test']);
        $book = TestDataFactory::book();

        $ownBorrow = TestDataFactory::borrow($member, $book);
        $otherBook = TestDataFactory::book(null, ['isbn' => '9780306406157']);
        $otherBorrow = TestDataFactory::borrow($otherMember, $otherBook);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_borrow', ['member_id' => $otherMember->id])
            ->assertOk()
            ->assertJsonFragment(['id' => $ownBorrow->id])
            ->assertJsonMissing(['id' => $otherBorrow->id]);
    }

    public function test_member_borrow_search_can_filter_by_status_without_exposing_other_members(): void
    {
        $member = TestDataFactory::member();
        $otherMember = TestDataFactory::member(['email' => 'status-filter-other@example.test']);

        $returnedBook = TestDataFactory::book(null, ['isbn' => '9780306406157']);
        $activeBook = TestDataFactory::book(null, ['isbn' => '9780306406164']);
        $otherReturnedBook = TestDataFactory::book(null, ['isbn' => '9780306406171']);

        $returnedBorrow = TestDataFactory::borrow($member, $returnedBook, [
            'status' => 'returned',
            'returned_at' => '2025-01-10',
        ]);
        $activeBorrow = TestDataFactory::borrow($member, $activeBook);
        $otherReturnedBorrow = TestDataFactory::borrow($otherMember, $otherReturnedBook, [
            'status' => 'returned',
            'returned_at' => '2025-01-10',
        ]);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_borrow', ['status' => 'returned'])
            ->assertOk()
            ->assertJsonFragment(['id' => $returnedBorrow->id])
            ->assertJsonMissing(['id' => $activeBorrow->id])
            ->assertJsonMissing(['id' => $otherReturnedBorrow->id]);
    }

    public function test_member_borrow_search_rejects_an_unsupported_status(): void
    {
        $member = TestDataFactory::member();

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_borrow', ['status' => 'pending'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_member_can_return_their_own_active_borrow_and_inventory_is_restored(): void
    {
        $member = TestDataFactory::member();
        $book = TestDataFactory::book(null, [
            'isbn' => '9780306406157',
            'total_copies' => 2,
            'available_copies' => 1,
        ]);
        $borrow = TestDataFactory::borrow($member, $book);

        $this->authenticateAsMember($member)
            ->putJson('/v1/member/back_borrow', ['id' => $borrow->id])
            ->assertOk()
            ->assertJsonPath('message', 'book back');

        $this->assertDatabaseHas('borrows', [
            'id' => $borrow->id,
            'status' => 'returned',
        ]);
        $this->assertSame(2, $book->fresh()->available_copies);
    }

    public function test_member_cannot_return_another_members_borrow(): void
    {
        $member = TestDataFactory::member();
        $otherMember = TestDataFactory::member(['email' => 'borrow-owner@example.test']);
        $book = TestDataFactory::book();
        $borrow = TestDataFactory::borrow($otherMember, $book);

        $this->authenticateAsMember($member)
            ->putJson('/v1/member/back_borrow', ['id' => $borrow->id])
            ->assertUnprocessable();
    }

    public function test_member_cannot_return_an_already_returned_borrow(): void
    {
        $member = TestDataFactory::member();
        $book = TestDataFactory::book();
        $borrow = TestDataFactory::borrow($member, $book, [
            'status' => 'returned',
            'returned_at' => '2025-01-10',
        ]);

        $this->authenticateAsMember($member)
            ->putJson('/v1/member/back_borrow', ['id' => $borrow->id])
            ->assertUnprocessable();
    }

    public function test_member_cannot_return_a_borrow_while_an_unpaid_penalty_exists(): void
    {
        $member = TestDataFactory::member();
        $book = TestDataFactory::book();
        $borrow = TestDataFactory::borrow($member, $book);
        TestDataFactory::penalty($member, $borrow);

        $this->authenticateAsMember($member)
            ->putJson('/v1/member/back_borrow', ['id' => $borrow->id])
            ->assertForbidden()
            ->assertJsonPath('message', 'you have unpaid penalties, first paid your penalties');
    }
    public function test_member_cannot_borrow_the_same_book_twice_on_the_same_day(): void
    {
        $member = TestDataFactory::member();
        $book = TestDataFactory::book();
        TestDataFactory::borrow($member, $book, [
            'borrowed_at' => now()->toDateString(),
        ]);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/add_borrow', ['book_id' => $book->id])
            ->assertUnprocessable();

        $this->assertDatabaseCount('borrows', 1);
    }

    public function test_member_can_borrow_after_their_unpaid_penalty_is_paid(): void
    {
        $member = TestDataFactory::member();
        $librarian = TestDataFactory::librarian();
        $previousBook = TestDataFactory::book();
        $previousBorrow = TestDataFactory::borrow($member, $previousBook);
        $penalty = TestDataFactory::penalty($member, $previousBorrow);
        $newBook = TestDataFactory::book(null, [
            'isbn' => '9780306406195',
            'total_copies' => 2,
            'available_copies' => 2,
        ]);

        $this->authenticateAsLibrarian($librarian)
            ->putJson('/v1/librarian/pay_penalty', ['id' => $penalty->id])
            ->assertOk()
            ->assertJsonPath('message', 'penalty paid successfully');

        $this->assertNotNull($penalty->fresh()->paid_at);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/add_borrow', ['book_id' => $newBook->id])
            ->assertCreated()
            ->assertJsonPath('message', 'borrow book successful');

        $this->assertDatabaseHas('borrows', [
            'member_id' => $member->id,
            'book_id' => $newBook->id,
            'status' => 'borrowed',
        ]);
        $this->assertSame(1, $newBook->fresh()->available_copies);
    }

}
