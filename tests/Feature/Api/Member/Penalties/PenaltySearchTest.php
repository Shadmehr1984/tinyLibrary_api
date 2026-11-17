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


    public function test_member_penalty_search_can_filter_calculated_before_date_strictly(): void
    {
        $member = TestDataFactory::member();
        $earlierBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406157']));
        $boundaryBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406164']));
        $laterBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406171']));

        $earlier = TestDataFactory::penalty($member, $earlierBorrow, ['calculated_at' => '2025-01-19']);
        $boundary = TestDataFactory::penalty($member, $boundaryBorrow, ['calculated_at' => '2025-01-20']);
        $later = TestDataFactory::penalty($member, $laterBorrow, ['calculated_at' => '2025-01-21']);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_penalty', ['calculated_before_at' => '2025-01-20'])
            ->assertOk()
            ->assertJsonFragment(['id' => $earlier->id])
            ->assertJsonMissing(['id' => $boundary->id])
            ->assertJsonMissing(['id' => $later->id]);
    }

    public function test_member_penalty_search_can_filter_calculated_after_date_strictly(): void
    {
        $member = TestDataFactory::member();
        $earlierBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406157']));
        $boundaryBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406164']));
        $laterBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406171']));

        $earlier = TestDataFactory::penalty($member, $earlierBorrow, ['calculated_at' => '2025-01-19']);
        $boundary = TestDataFactory::penalty($member, $boundaryBorrow, ['calculated_at' => '2025-01-20']);
        $later = TestDataFactory::penalty($member, $laterBorrow, ['calculated_at' => '2025-01-21']);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_penalty', ['calculated_after_at' => '2025-01-20'])
            ->assertOk()
            ->assertJsonFragment(['id' => $later->id])
            ->assertJsonMissing(['id' => $boundary->id])
            ->assertJsonMissing(['id' => $earlier->id]);
    }

    public function test_member_penalty_search_can_filter_paid_before_date_strictly(): void
    {
        $member = TestDataFactory::member();
        $earlierBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406157']));
        $boundaryBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406164']));
        $laterBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406171']));
        $unpaidBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406188']));

        $earlier = TestDataFactory::penalty($member, $earlierBorrow, ['paid_at' => '2025-01-19']);
        $boundary = TestDataFactory::penalty($member, $boundaryBorrow, ['paid_at' => '2025-01-20']);
        $later = TestDataFactory::penalty($member, $laterBorrow, ['paid_at' => '2025-01-21']);
        $unpaid = TestDataFactory::penalty($member, $unpaidBorrow);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_penalty', ['paid_before_at' => '2025-01-20'])
            ->assertOk()
            ->assertJsonFragment(['id' => $earlier->id])
            ->assertJsonMissing(['id' => $boundary->id])
            ->assertJsonMissing(['id' => $later->id])
            ->assertJsonMissing(['id' => $unpaid->id]);
    }

    public function test_member_penalty_search_can_filter_paid_after_date_strictly(): void
    {
        $member = TestDataFactory::member();
        $earlierBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406157']));
        $boundaryBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406164']));
        $laterBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406171']));
        $unpaidBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406188']));

        $earlier = TestDataFactory::penalty($member, $earlierBorrow, ['paid_at' => '2025-01-19']);
        $boundary = TestDataFactory::penalty($member, $boundaryBorrow, ['paid_at' => '2025-01-20']);
        $later = TestDataFactory::penalty($member, $laterBorrow, ['paid_at' => '2025-01-21']);
        $unpaid = TestDataFactory::penalty($member, $unpaidBorrow);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_penalty', ['paid_after_at' => '2025-01-20'])
            ->assertOk()
            ->assertJsonFragment(['id' => $later->id])
            ->assertJsonMissing(['id' => $boundary->id])
            ->assertJsonMissing(['id' => $earlier->id])
            ->assertJsonMissing(['id' => $unpaid->id]);
    }


    public function test_member_penalty_search_filters_by_exact_amount(): void
    {
        $member = TestDataFactory::member();
        $matchingBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406157']));
        $lowerBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406164']));
        $higherBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406171']));

        $matching = TestDataFactory::penalty($member, $matchingBorrow, ['amount' => 1000]);
        $lower = TestDataFactory::penalty($member, $lowerBorrow, ['amount' => 500]);
        $higher = TestDataFactory::penalty($member, $higherBorrow, ['amount' => 1500]);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_penalty', ['amount' => 1000])
            ->assertOk()
            ->assertJsonFragment(['id' => $matching->id])
            ->assertJsonMissing(['id' => $lower->id])
            ->assertJsonMissing(['id' => $higher->id]);
    }

    public function test_member_penalty_search_filters_by_borrowed_id(): void
    {
        $member = TestDataFactory::member();
        $matchingBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406157']));
        $otherBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406164']));

        $matching = TestDataFactory::penalty($member, $matchingBorrow);
        $other = TestDataFactory::penalty($member, $otherBorrow);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_penalty', ['borrowed_id' => $matchingBorrow->id])
            ->assertOk()
            ->assertJsonFragment(['id' => $matching->id])
            ->assertJsonMissing(['id' => $other->id]);
    }

    public function test_member_penalty_search_filters_by_exact_calculated_date(): void
    {
        $member = TestDataFactory::member();
        $matchingBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406157']));
        $otherBorrow = TestDataFactory::borrow($member, TestDataFactory::book(null, ['isbn' => '9780306406164']));

        $matching = TestDataFactory::penalty($member, $matchingBorrow, ['calculated_at' => '2025-01-20']);
        $other = TestDataFactory::penalty($member, $otherBorrow, ['calculated_at' => '2025-01-21']);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_penalty', ['calculated_at' => '2025-01-20'])
            ->assertOk()
            ->assertJsonFragment(['id' => $matching->id])
            ->assertJsonMissing(['id' => $other->id]);
    }

    public function test_member_penalty_search_rejects_non_positive_numeric_filters(): void
    {
        $member = TestDataFactory::member();

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_penalty', [
                'borrowed_id' => 0,
                'amount' => 0,
                'amount_lower_than' => 0,
                'amount_greater_than' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'borrowed_id',
                'amount',
                'amount_lower_than',
                'amount_greater_than',
            ]);
    }

}
