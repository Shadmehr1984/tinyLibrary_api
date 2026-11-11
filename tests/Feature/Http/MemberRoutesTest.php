<?php

namespace Tests\Feature\Http;

use App\Models\Member;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class MemberRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_enter_non_exists_member_info_return_error_on_login(): void
    {
        $data = [
            'email' => 'nonExistsEmail@gmail.com',
            'password' => 'random shit'
        ];

        $response = $this->postJson('api/v1/member/login', $data);

        $response->assertJsonPath('message', 'invalid email or password');

        $response->assertUnauthorized();
    }

    public function test_enter_wrong_password_must_return_same_error_message_as_non_exists_email_on_login(): void
    {
        Member::factory()->create(
            [
                'email' => 'ssszzzast@gmail.com',
                'password' => Hash::make('ssszzzast21'),
                'phone' => '+989903452720'
            ]
        );

        $data = [
            'email' => 'ssszzzast@gmail.com',
            'password' => 'wrong password'
        ];

        $response = $this->postJson('api/v1/member/login', $data);

        $response->assertUnauthorized();

        $response->assertJsonPath('message', 'invalid email or password');
    }

    public function test_login_response_must_return_correct_token_on_login(): void
    {
        $member = Member::factory()->create(
            [
                'email' => 'ssszzzast@gmail.com',
                'password' => Hash::make('ssszzzast21'),
                'phone' => '+989903452720'
            ]
        );

        $data = [
            'email' => 'ssszzzast@gmail.com',
            'password' => 'ssszzzast21'
        ];

        $response = $this->postJson('api/v1/member/login', $data);

        $response->assertOk();

        $token = $response->json('token');

        $access_token = PersonalAccessToken::findToken($token);

        $this->assertSame($member->id, $access_token->tokenable_id);
    }

    public function test_unauthorize_member_can_not_send_request_on_endpoints(): void
    {
        $endpoints = [
            'api/v1/member/logout',
            'api/v1/member/search_book',
            'api/v1/member/add_borrow',
            'api/v1/member/search_borrow',
            'api/v1/member/back_borrow',
            'api/v1/member/search_penalty'
        ];

        $data = [];

        foreach($endpoints as $endpoint){
            $method = 'postJson';
            if ($endpoint == 'api/v1/member/back_borrow'){
                $method = 'putJson';
            }
            $response = $this->$method($endpoint, $data);

            $response->assertUnauthorized();

            $response->assertJsonPath('message', 'Unauthenticated.');
        }
    }

    public function test_deactivated_member_can_not_send_request_on_endpoints(): void
    {
        $endpoints = [
            'api/v1/member/logout',
            'api/v1/member/search_book',
            'api/v1/member/add_borrow',
            'api/v1/member/search_borrow',
            'api/v1/member/back_borrow',
            'api/v1/member/search_penalty'
        ];

        $member = Member::factory()->create([
            'phone' => '+989903452720',
            'active' => false
        ]);
        if (empty($member)){
            $member = (Member::class)($member);
        }

        $data = [];

        foreach($endpoints as $endpoint){
            $method = 'postJson';
            if ($endpoint == 'api/v1/member/back_borrow'){
                $method = 'putJson';
            }
            $response = $this->actingAs($member)->$method($endpoint, $data);

            $response->assertForbidden();

            $response->assertJsonPath('message', 'member is de active');
        }
    }
}
