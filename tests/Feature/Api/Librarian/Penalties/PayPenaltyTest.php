<?php

namespace Tests\Feature\Api\Librarian\Penalties;

use Tests\Support\Api\ApiTestCase;
use Tests\Support\Factories\TestDataFactory;
use Tests\Support\Helpers\AuthenticatesApiUsers;

class PayPenaltyTest extends ApiTestCase
{
    use AuthenticatesApiUsers;

    public function test_librarian_can_pay_an_unpaid_penalty(): void
    {
        $librarian = TestDataFactory::librarian();
        $member = TestDataFactory::member();
        $book = TestDataFactory::book();
        $borrow = TestDataFactory::borrow($member, $book);
        $penalty = TestDataFactory::penalty($member, $borrow);

        $this->authenticateAsLibrarian($librarian)
            ->putJson('/v1/librarian/pay_penalty', ['id' => $penalty->id])
            ->assertOk()
            ->assertJsonPath('message', 'penalty paid successfully');

        $this->assertNotNull($penalty->fresh()->paid_at);
    }

    public function test_librarian_cannot_pay_an_already_paid_penalty_twice(): void
    {
        $librarian = TestDataFactory::librarian();
        $member = TestDataFactory::member();
        $book = TestDataFactory::book();
        $borrow = TestDataFactory::borrow($member, $book);
        $penalty = TestDataFactory::penalty($member, $borrow, ['paid_at' => '2025-01-20']);

        $this->authenticateAsLibrarian($librarian)
            ->putJson('/v1/librarian/pay_penalty', ['id' => $penalty->id])
            ->assertStatus(409)
            ->assertJsonPath('message', 'penalty already paid');
    }
    public function test_pay_penalty_requires_an_existing_penalty_id(): void
    {
        $this->authenticateAsLibrarian(TestDataFactory::librarian())
            ->putJson('/v1/librarian/pay_penalty', ['id' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id']);
    }


    public function test_pay_penalty_requires_the_id_field(): void
    {
        $this->authenticateAsLibrarian(TestDataFactory::librarian())
            ->putJson('/v1/librarian/pay_penalty', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id']);
    }

    public function test_pay_penalty_rejects_a_non_positive_id(): void
    {
        $this->authenticateAsLibrarian(TestDataFactory::librarian())
            ->putJson('/v1/librarian/pay_penalty', ['id' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id']);
    }

}
