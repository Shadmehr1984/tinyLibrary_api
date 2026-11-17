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

    public function test_member_penalty_search_filters_amount_below_a_strict_boundary(): void
    {
        $member = TestDataFactory::member();
        $belowBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406157']));
        $boundaryBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406164']));
        $aboveBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406171']));

        $below = TestDataFactory::penalty($member, $belowBorrow, ['amount' => 500]);
        $boundary = TestDataFactory::penalty($member, $boundaryBorrow, ['amount' => 1000]);
        $above = TestDataFactory::penalty($member, $aboveBorrow, ['amount' => 1500]);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_penalty', ['amount_lower_than' => 1000])
            ->assertOk()
            ->assertJsonFragment(['id' => $below->id])
            ->assertJsonMissing(['id' => $boundary->id])
            ->assertJsonMissing(['id' => $above->id]);
    }

    public function test_member_penalty_search_filters_amount_above_a_strict_boundary(): void
    {
        $member = TestDataFactory::member();
        $belowBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406157']));
        $boundaryBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406164']));
        $aboveBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406171']));

        $below = TestDataFactory::penalty($member, $belowBorrow, ['amount' => 500]);
        $boundary = TestDataFactory::penalty($member, $boundaryBorrow, ['amount' => 1000]);
        $above = TestDataFactory::penalty($member, $aboveBorrow, ['amount' => 1500]);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_penalty', ['amount_greater_than' => 1000])
            ->assertOk()
            ->assertJsonFragment(['id' => $above->id])
            ->assertJsonMissing(['id' => $boundary->id])
            ->assertJsonMissing(['id' => $below->id]);
    }

    public function test_member_penalty_search_can_filter_by_exact_paid_date(): void
    {
        $member = TestDataFactory::member();
        $paidBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406157']));
        $otherPaidBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406164']));
        $unpaidBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406171']));

        $paid = TestDataFactory::penalty($member, $paidBorrow, ['paid_at' => '2025-01-20']);
        $otherPaid = TestDataFactory::penalty($member, $otherPaidBorrow, ['paid_at' => '2025-01-21']);
        $unpaid = TestDataFactory::penalty($member, $unpaidBorrow);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_penalty', ['paid_at' => '2025-01-20'])
            ->assertOk()
            ->assertJsonFragment(['id' => $paid->id])
            ->assertJsonMissing(['id' => $otherPaid->id])
            ->assertJsonMissing(['id' => $unpaid->id]);
    }

    public function test_member_penalty_search_rejects_a_zero_limit(): void
    {
        $member = TestDataFactory::member();

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_penalty', [
                'amount' => 1000,
                'limit' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['limit']);
    }

}
