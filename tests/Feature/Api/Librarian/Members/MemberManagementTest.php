<?php

namespace Tests\Feature\Api\Librarian\Members;

use Illuminate\Support\Facades\Hash;
use Tests\Support\Api\ApiTestCase;
use Tests\Support\Factories\TestDataFactory;
use Tests\Support\Helpers\AuthenticatesApiUsers;

class MemberManagementTest extends ApiTestCase
{
    use AuthenticatesApiUsers;

    public function test_librarian_can_add_a_member_with_a_hashed_password(): void
    {
        $librarian = TestDataFactory::librarian();

        $this->authenticateAsLibrarian($librarian)
            ->postJson('/v1/librarian/add_member', [
                'name' => 'New Library Member',
                'email' => 'new-member@example.test',
                'password' => 'member-password',
                'phone' => '+10000000002',
                'address' => 'New Member Address',
            ])->assertCreated()
            ->assertJsonPath('message', 'member created');

        $this->assertDatabaseHas('members', [
            'email' => 'new-member@example.test',
            'active' => true,
            'penalty_balance' => 0,
        ]);

        $member = \App\Models\Member::where('email', 'new-member@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('member-password', $member->password));
    }

    public function test_librarian_can_deactivate_and_reactivate_a_member(): void
    {
        $librarian = TestDataFactory::librarian();
        $member = TestDataFactory::member(['email' => 'status-member@example.test']);

        $this->authenticateAsLibrarian($librarian)
            ->putJson('/v1/librarian/deactivate_member', ['email' => $member->email])
            ->assertOk()
            ->assertJsonPath('message', 'member deactivated');

        $this->assertDatabaseHas('members', ['id' => $member->id, 'active' => false]);

        $this->putJson('/v1/librarian/activate_member', ['email' => $member->email])
            ->assertOk()
            ->assertJsonPath('message', 'member activated');

        $this->assertDatabaseHas('members', ['id' => $member->id, 'active' => true]);
    }

    public function test_librarian_can_update_member_profile(): void
    {
        $librarian = TestDataFactory::librarian();
        $member = TestDataFactory::member(['email' => 'update-member@example.test']);

        $this->authenticateAsLibrarian($librarian)
            ->putJson('/v1/librarian/update_member', [
                'target_email' => $member->email,
                'name' => 'Updated Member Name',
                'address' => 'Updated Address',
            ])->assertOk()
            ->assertJsonPath('message', 'member updated');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'name' => 'Updated Member Name',
            'address' => 'Updated Address',
        ]);
    }

    public function test_librarian_can_search_members_without_exposing_passwords(): void
    {
        $librarian = TestDataFactory::librarian();
        $member = TestDataFactory::member(['email' => 'search-member@example.test']);

        $this->authenticateAsLibrarian($librarian)
            ->postJson('/v1/librarian/search_member', ['email' => $member->email])
            ->assertOk()
            ->assertJsonPath('members.0.email', $member->email)
            ->assertJsonMissingPath('members.0.password');
    }

    public function test_librarian_can_delete_a_member_without_dependent_records(): void
    {
        $librarian = TestDataFactory::librarian();
        $member = TestDataFactory::member(['email' => 'delete-member@example.test']);

        $this->authenticateAsLibrarian($librarian)
            ->deleteJson('/v1/librarian/delete_member', ['email' => $member->email])
            ->assertOk()
            ->assertJsonPath('message', 'member deleted');

        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    }

    public function test_member_search_can_filter_inactive_members(): void
    {
        $librarian = TestDataFactory::librarian();
        $inactiveMember = TestDataFactory::member([
            'email' => 'inactive-search@example.test',
            'active' => false,
        ]);
        $activeMember = TestDataFactory::member([
            'email' => 'active-search@example.test',
            'active' => true,
        ]);

        $this->authenticateAsLibrarian($librarian)
            ->postJson('/v1/librarian/search_member', ['active' => false])
            ->assertOk()
            ->assertJsonFragment(['email' => $inactiveMember->email])
            ->assertJsonMissing(['email' => $activeMember->email]);
    }

    public function test_add_member_rejects_an_invalid_phone_number(): void
    {
        $this->authenticateAsLibrarian(TestDataFactory::librarian())
            ->postJson('/v1/librarian/add_member', [
                'name' => 'Invalid Phone Member',
                'email' => 'invalid-phone@example.test',
                'password' => 'member-password',
                'phone' => '+not-a-number',
                'address' => 'Member Address',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_member_password_updates_are_stored_as_a_hash(): void
    {
        $librarian = TestDataFactory::librarian();
        $member = TestDataFactory::member(['email' => 'password-update@example.test']);

        $this->authenticateAsLibrarian($librarian)
            ->putJson('/v1/librarian/update_member', [
                'target_email' => $member->email,
                'password' => 'new-member-password',
            ])->assertOk();

        $updatedMember = $member->fresh();
        $this->assertNotSame('new-member-password', $updatedMember->password);
        $this->assertTrue(Hash::check('new-member-password', $updatedMember->password));
    }

}
