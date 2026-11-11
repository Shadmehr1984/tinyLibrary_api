<?php

namespace Tests\Http\Feature;

use App\Models\Member;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class APITest extends TestCase
{
    use RefreshDatabase;

    private $member;
    private $member_token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = Member::factory()->create(
            [
                'email' => 'ssszzzast@gmail.com',
                'password' => Hash::make('ssszzzast21'),
                'phone' => "+989903452720"
            ]
        );

        $this->member_token = $this->member->createToken('member-token')->plainTextToken;
    }

    public function test_simple_get(): void
    {
        $response = $this->get('test');

        $response->assertStatus(200);
    }

    public function test_member_search_request_must_return_code_401(): void
    {
        $response = $this->postJson('api/v1/member/search_book', []);

        $response->assertUnauthorized();

        //$response->ddBody();

        $response->assertJsonStructure([
            'message'
        ]);

        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_invalid_isbn_return_422_code_unprocessable_entity(): void
    {
        $response = $this->actingAs($this->member, 'member')->postJson('api/v1/member/search_book', [
            'isbn' => "A56463133"
        ]);

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors(['isbn']);

        $response->assertJsonStructure([
            'message',
            'errors' => [
                'isbn'
            ]
        ]);
    }
}
