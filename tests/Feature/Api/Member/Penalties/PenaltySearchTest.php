<?php

namespace Tests\Feature\Api\Member\Penalties;

use Tests\Support\Api\ApiTestCase;
use Tests\Support\Factories\TestDataFactory;
use Tests\Support\Helpers\AuthenticatesApiUsers;

class PenaltySearchTest extends ApiTestCase
{
    use AuthenticatesApiUsers;

    public function test_member_penalty_search_is_scoped_to_authenticated_member(): void
    {
        $member = TestDataFactory::member();
        $otherMember = TestDataFactory::member(['email' => 'other-penalty-member@example.test']);

        $book = TestDataFactory::book();
        $ownBorrow = TestDataFactory::borrow($member, $book);
        $ownPenalty = TestDataFactory::penalty($member, $ownBorrow);

        $otherBook = TestDataFactory::book(null, ['isbn' => '9780306406157']);
        $otherBorrow = TestDataFactory::borrow($otherMember, $otherBook);
        $otherPenalty = TestDataFactory::penalty($otherMember, $otherBorrow);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_penalty', ['member_id' => $otherMember->id])
            ->assertOk()
            ->assertJsonFragment(['id' => $ownPenalty->id])
            ->assertJsonMissing(['id' => $otherPenalty->id]);
    }

    public function test_inactive_member_cannot_search_penalties(): void
    {
        $member = TestDataFactory::member(['active' => false]);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_penalty', ['amount' => 1000])
            ->assertForbidden();
    }
}
