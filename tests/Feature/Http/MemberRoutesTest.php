<?php

namespace Tests\Feature\Http;

use App\Models\Book;
use App\Models\Category;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MemberRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function authenticated_member(): array
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

        $token = $response->json('token');

        return [$member, $token];
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

        foreach ($endpoints as $endpoint) {
            $method = 'postJson';
            if ($endpoint == 'api/v1/member/back_borrow') {
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
        if (empty($member)) {
            $member = (Member::class)($member);
        }

        $data = [];

        foreach ($endpoints as $endpoint) {
            $method = 'postJson';
            if ($endpoint == 'api/v1/member/back_borrow') {
                $method = 'putJson';
            }
            $response = $this->actingAs($member)->$method($endpoint, $data);

            $response->assertForbidden();

            $response->assertJsonPath('message', 'member is de active');
        }
    }

    public function test_unauthenticated_request_on_logout_must_return_401(): void
    {
        $response = $this->postJson('api/v1/member/logout');

        $response->assertUnauthorized();

        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_logout_should_delete_access_token(): void
    {
        //login
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

        //logout
        $response = $this->withToken($token)->postJson('api/v1/member/logout', []);

        $response->assertOk();

        $response->assertJsonPath('message', 'successful logout');

        $this->assertNull(PersonalAccessToken::findToken($token));
    }

    public function test_search_book_endpoint_return_422_when_invalid_title_given(): void
    {
        [$member, $token] = $this->authenticated_member();

        $data = [
            'title' => 123456789
        ];

        $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

        $response->assertUnprocessable();

        $response->assertJsonPath('message', 'The title field must be a string.');
    }

    public function test_search_book_endpoint_return_422_when_invalid_author_given(): void
    {
        [$member, $token] = $this->authenticated_member();

        $data = [
            'author' => 123456789
        ];

        $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

        $response->assertUnprocessable();

        $response->assertJsonPath('message', 'The author field must be a string.');
    }

    public static function wrong_isbn(): array
    {
        return [
            'lower_than_right_size' => ['4568468516'],
            'greater_than_right_size' => ['459723684687568'],
            'has_alphabetic' => ['4587964123t41']
        ];
    }

    #[DataProvider('wrong_isbn')]
    public function test_search_book_endpoint_return_422_when_invalid_isbn_given($wrong_isbn): void
    {
        [$member, $token] = $this->authenticated_member();

        $data = [
            'isbn' => $wrong_isbn
        ];

        $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

        $response->assertUnprocessable();
    }

    public function test_search_book_endpoint_return_422_when_non_exists_isbn_given(): void
    {
        [$member, $token] = $this->authenticated_member();

        $data = [
            'isbn' => '4568751234568'
        ];

        $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

        $response->assertUnprocessable();

        $response->assertJsonPath('message', 'The selected isbn is invalid.');
    }

    public static function wrong_date_format(): array
    {
        return [
            'wrong_day' => ['2005-04-1'],
            'wrong_month' => ['2004-4-23'],
            'wrong_year' => ['210-10-10'],
            'uses_slashes' => ['2005/04/20']
        ];
    }

    #[DataProvider('wrong_date_format')]
    public function test_search_book_endpoint_return_422_when_invalid_published_date_format_given($wrong_date_format): void
    {
        [$member, $token] = $this->authenticated_member();

        $published_fields = [
            'published',
            'published_before',
            'published_after'
        ];

        foreach ($published_fields as $published_field) {

            $data = [
                $published_field => $wrong_date_format
            ];

            $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

            $response->assertUnprocessable();
        }
    }

    public function test_search_book_endpoint_return_422_when_invalid_publisher_given(): void
    {
        [$member, $token] = $this->authenticated_member();

        $data = [
            'publisher' => 123456789
        ];

        $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

        $response->assertUnprocessable();

        $response->assertJsonPath('message', 'The publisher field must be a string.');
    }

    public static function wrong_category_id(): array
    {
        return [
            'non_numeric' => ['a'],
            'lower_than_zero' => [-4],
            'non_exists_id' => [1]
        ];
    }

    #[DataProvider('wrong_category_id')]
    public function test_search_book_endpoint_return_422_when_invalid_category_id_given($wrong_category_id): void
    {
        [$member, $token] = $this->authenticated_member();

        $data = [
            'category_id' => $wrong_category_id
        ];

        $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

        $response->assertUnprocessable();
    }

    public static function wrong_copies(): array
    {
        return [
            'non_numeric' => ['a'],
            'lower_than_zero' => [-4]
        ];
    }

    #[DataProvider('wrong_copies')]
    public function test_search_book_endpoint_return_422_when_invalid_copies_given($wrong_copies): void
    {
        [$member, $token] = $this->authenticated_member();

        $copies_fields = [
            'total_copies',
            'total_copies_lower_than',
            'total_copies_greater_than',
            'available_copies',
            'available_copies_lower_than',
            'available_copies_greater_than'
        ];

        foreach ($copies_fields as $copies_field) {

            $data = [
                $copies_field => $wrong_copies
            ];

            $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

            $response->assertUnprocessable();
        }
    }

    public static function wrong_description(): array
    {
        return [
            'numeric' => [456786341],
            'lower_than_min' => ['akdyhg'],
            'greater_than_max' => ['jfdjfdupiorjvnkijwscfkjnhihdnikcjnhodpewapjcnk;dasnkjcn;klojehc;ldsajd;s']
        ];
    }

    #[DataProvider('wrong_description')]
    public function test_search_book_endpoint_return_422_when_invalid_description_given($wrong_description): void
    {
        [$member, $token] = $this->authenticated_member();

        $data = [
            'description' => $wrong_description
        ];

        $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

        $response->assertUnprocessable();
    }

    public function test_search_book_endpoint_return_422_when_invalid_location_given(): void
    {
        [$member, $token] = $this->authenticated_member();

        $data = [
            'location' => 123456789
        ];

        $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

        $response->assertUnprocessable();
    }

    public static function wrong_available()
    {
        return [
            'numeric' => [6],
            'string' => ['wrong string']
        ];
    }

    #[DataProvider('wrong_available')]
    public function test_search_book_endpoint_return_422_when_invalid_available_given($wrong_available): void
    {
        [$member, $token] = $this->authenticated_member();

        $data = [
            'available' => $wrong_available
        ];

        $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

        $response->assertUnprocessable();
    }

    public static function wrong_not_available()
    {
        return [
            'numeric' => [6],
            'string' => ['wrong string']
        ];
    }

    #[DataProvider('wrong_not_available')]
    public function test_search_book_endpoint_return_422_when_invalid_not_available_given($wrong_not_available): void
    {
        [$member, $token] = $this->authenticated_member();

        $data = [
            'available' => $wrong_not_available
        ];

        $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

        $response->assertUnprocessable();
    }

    public function test_search_book_endpoint_return_422_when_available_and_not_available_in_same_time_given(): void
    {
        [$member, $token] = $this->authenticated_member();

        $data = [
            'available' => true,
            'not_available' => true
        ];

        $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

        $response->assertUnprocessable();
    }

    public static function wrong_limit(): array
    {
        return [
            'non_numeric' => ['a'],
            'lower_than_zero' => [-4]
        ];
    }

    #[DataProvider('wrong_limit')]
    public function test_search_book_endpoint_return_422_when_invalid_limit_given($wrong_limit): void
    {
        [$member, $token] = $this->authenticated_member();

        $data = [
            'limit' => $wrong_limit
        ];

        $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

        $response->assertUnprocessable();
    }

    public function test_search_book_endpoint_search_book_by_title(): void
    {
        [$member, $token] = $this->authenticated_member();

        Book::factory()->for(Category::factory())->create(
            [
                'title' => 'hojjat daily'
            ]
        );

        $data = [
            'title' => 'hojjat'
        ];

        $response = $this->withToken($token)->postJson('api/v1/member/search_book', $data);

        //$response->ddBody();
        $expected = ['books' => [
            [
                'title' => "hojjat daily"
            ]
        ]];
        
        $response->assertJson($expected);
    }
}
