<?php

namespace Tests\Feature\Api\Authentication;

use AppModels\Librarian;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Api\ApiTestCase;
use Tests\Support\Factories\TestDataFactory;
use Tests\Support\Helpers\AuthenticatesApiUsers;

class AuthenticationTest extends ApiTestCase
{
    use AuthenticatesApiUsers;

    public function test_member_can_log_in_and_receive_a_sanctum_token(): void
    {
        $member = TestDataFactory::member([
            'email' => 'member-login@example.test',
            'password' => Hash::make('password-123'),
        ]);

        $response = $this->postJson('/v1/member/login', [
            'email' => $member->email,
            'password' => 'password-123',
        ]);

        $response->assertOk()
            ->assertJsonPath('member.email', $member->email)
            ->assertJsonStructure(['massage', 'member', 'token']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $member->id,
            'tokenable_type' => Member::class,
        ]);
    }

    public function test_librarian_can_log_in_and_receive_a_sanctum_token(): void
    {
        $librarian = TestDataFactory::librarian([
            'email' => 'librarian-login@example.test',
            'password' => Hash::make('password-123'),
        ]);

        $response = $this->postJson('/v1/librarian/login', [
            'email' => $librarian->email,
            'password' => 'password-123',
        ]);

        $response->assertOk()
            ->assertJsonPath('librarian.email', $librarian->email)
            ->assertJsonStructure(['massage', 'librarian', 'token']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $librarian->id,
            'tokenable_type' => Librarian::class,
        ]);
    }

    public function test_member_login_rejects_invalid_credentials(): void
    {
        $member = TestDataFactory::member([
            'email' => 'member-invalid@example.test',
            'password' => Hash::make('password-123'),
        ]);

        $this->postJson('/v1/member/login', [
            'email' => $member->email,
            'password' => 'incorrect-123',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'invalid email or password');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_librarian_login_rejects_unknown_identity(): void
    {
        $this->postJson('/v1/librarian/login', [
            'email' => 'missing@example.test',
            'password' => 'password-123',
        ])->assertUnauthorized()
            ->assertJsonPath('massage', 'invalid email or password');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_endpoints_validate_required_credentials(): void
    {
        $this->postJson('/v1/member/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);

        $this->postJson('/v1/librarian/login', [
            'email' => 'not-an-email',
            'password' => 'short',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_inactive_member_cannot_log_in(): void
    {
        TestDataFactory::member([
            'email' => 'inactive-login@example.test',
            'password' => Hash::make('password-123'),
            'active' => false,
        ]);

        $this->postJson('/v1/member/login', [
            'email' => 'inactive-login@example.test',
            'password' => 'password-123',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'invalid email or password');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_member_token_cannot_access_librarian_routes(): void
    {
        $member = TestDataFactory::member();

        $this->authenticateAsMember($member)
            ->postJson('/v1/librarian/logout')
            ->assertUnauthorized();
    }

    public function test_librarian_token_cannot_access_member_routes(): void
    {
        $librarian = TestDataFactory::librarian();

        $this->authenticateAsLibrarian($librarian)
            ->postJson('/v1/member/search_borrow')
            ->assertUnauthorized();
    }

    public function test_member_logout_revokes_the_current_token(): void
    {
        $member = TestDataFactory::member();
        $token = $this->memberToken($member);

        $this->withToken($token)
            ->postJson('/v1/member/logout')
            ->assertOk()
            ->assertJsonPath('message', 'successful logout');

        $this->withToken($token)
            ->postJson('/v1/member/search_borrow')
            ->assertUnauthorized();
    }

    public function test_inactive_member_is_denied_by_protected_member_middleware(): void
    {
        $member = TestDataFactory::member(['active' => false]);

        $this->authenticateAsMember($member)
            ->postJson('/v1/member/search_borrow')
            ->assertForbidden()
            ->assertJsonPath('message', 'member is de active');
    }

    public function test_librarian_logout_revokes_the_current_token(): void
    {
        $librarian = TestDataFactory::librarian();
        $token = $this->librarianToken($librarian);

        $this->withToken($token)
            ->postJson('/v1/librarian/logout')
            ->assertOk()
            ->assertJsonPath('message', 'successful logout');

        $this->withToken($token)
            ->postJson('/v1/librarian/logout')
            ->assertUnauthorized();
    }

    public function test_protected_routes_reject_a_malformed_bearer_token(): void
    {
        $this->withToken('not-a-valid-token')
            ->postJson('/v1/member/search_borrow')
            ->assertUnauthorized();

        $this->withToken('not-a-valid-token')
            ->postJson('/v1/librarian/logout')
            ->assertUnauthorized();
    }
}
