<?php

namespace Tests\Feature\Repositories;

use App\Domain\Entities\Category as CategoryEntity;
use App\Domain\Entities\Borrow as BorrowEntity;
use App\Domain\Entities\Librarian as LibrarianEntity;
use App\Domain\Entities\Member as MemberEntity;
use App\Domain\Entities\Penalty as PenaltyEntity;
use App\Domain\ValueObjects\BorrowStatus;
use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Phone;
use App\Repositories\BorrowRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\LibrarianRepository;
use App\Repositories\MemberRepository;
use App\Repositories\PenaltyRepository;
use App\Repositories\Exceptions\MemberNotExistsException;
use App\Services\BorrowServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RepositoryOperationsTest extends TestCase
{
    use RefreshDatabase;

    private int $categoryId;
    private int $bookId;
    private int $memberId;
    private int $borrowId;
    private int $penaltyId;
    private int $librarianId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryId = DB::table('categories')->insertGetId([
            'name' => 'Programming',
            'description' => 'Programming books',
        ]);

        $this->bookId = DB::table('books')->insertGetId([
            'title' => 'Repository Testing',
            'author' => 'Test Author',
            'isbn' => '9780132350884',
            'published' => '2020-01-01',
            'publisher' => 'Test Press',
            'category_id' => $this->categoryId,
            'total_copies' => 5,
            'available_copies' => 4,
            'description' => 'A test book',
            'location' => 'A-01',
            'deleted_at' => null,
        ]);

        $this->memberId = DB::table('members')->insertGetId([
            'name' => 'Test Member',
            'email' => 'member@example.com',
            'password' => 'hashed-password',
            'phone' => '+491234567890',
            'address' => 'Test address',
            'membership_date' => '2020-01-01',
            'active' => true,
            'penalty_balance' => 0,
        ]);

        $this->librarianId = DB::table('librarians')->insertGetId([
            'name' => 'Test Librarian',
            'email' => 'librarian@example.com',
            'password' => 'hashed-password',
            'phone' => '+491234567891',
            'address' => 'Library address',
        ]);

        $this->borrowId = DB::table('borrows')->insertGetId([
            'member_id' => $this->memberId,
            'book_id' => $this->bookId,
            'borrowed_at' => '2000-01-01',
            'due_date' => null,
            'returned_at' => null,
            'status' => 'borrowed',
            'penalty_amount' => 0,
        ]);

        $this->penaltyId = DB::table('penalties')->insertGetId([
            'borrowed_id' => $this->borrowId,
            'member_id' => $this->memberId,
            'amount' => 100,
            'calculated_at' => '2000-01-01',
            'paid_at' => null,
        ]);
    }

    public function test_category_repository_search_supports_where_where_column_and_limit(): void
    {
        $matches = CategoryRepository::search([['name', '=', 'Programming']]);
        $this->assertCount(1, $matches);
        $this->assertInstanceOf(CategoryEntity::class, $matches[0]);

        $columnMatches = CategoryRepository::search([['id', '=', 'id', 'whereColumn']], 1);
        $this->assertCount(1, $columnMatches);

        $this->assertSame([], CategoryRepository::search([['name', '=', 'missing']]));
    }

    public function test_category_repository_saves_updates_changes_and_deletes_entities(): void
    {
        $newCategory = new CategoryEntity(null, 'Databases', 'Database books');
        $newRepository = new CategoryRepository($newCategory);
        $newRepository->save();

        $this->assertDatabaseHas('categories', ['name' => 'Databases']);

        $category = new CategoryEntity(
            $this->categoryId,
            'Programming',
            'Programming books'
        );
        $repository = new CategoryRepository($category);
        $repository->update(['name' => 'Software']);
        $repository->save();

        $this->assertDatabaseHas('categories', ['id' => $this->categoryId, 'name' => 'Software']);

        $repository->change(new CategoryEntity(null, 'Networks', 'Networking books'));
        $repository->save();
        $this->assertDatabaseHas('categories', ['name' => 'Networks']);

        $deleteRepository = new CategoryRepository(
            new CategoryEntity($this->categoryId, 'Software', 'Programming books')
        );
        $deleteRepository->delete();
        $this->assertDatabaseMissing('categories', ['id' => $this->categoryId]);
    }

    public function test_member_repository_search_and_member_borrow_count(): void
    {
        $members = MemberRepository::search([['id', '=', $this->memberId]]);
        $this->assertCount(1, $members);
        $this->assertInstanceOf(MemberEntity::class, $members[0]);
        $this->assertSame(1, MemberRepository::member_borrows($this->memberId));

        $this->expectException(MemberNotExistsException::class);
        MemberRepository::member_borrows(999999);
    }

    public function test_librarian_repository_search_builds_librarian_entity(): void
    {
        $librarians = LibrarianRepository::search([['id', '=', $this->librarianId]]);

        $this->assertCount(1, $librarians);
        $this->assertInstanceOf(LibrarianEntity::class, $librarians[0]);
        $this->assertSame('librarian@example.com', $librarians[0]->get()['email']->get());

        $repository = new LibrarianRepository($librarians[0]);
        $repository->save();

        $this->assertDatabaseHas('librarians', [
            'id' => $this->librarianId,
            'email' => 'librarian@example.com',
        ]);
    }

    public function test_penalty_repository_queries_and_updates_penalties(): void
    {
        $penalties = PenaltyRepository::search([['id', '=', $this->penaltyId]]);
        $this->assertCount(1, $penalties);
        $this->assertInstanceOf(PenaltyEntity::class, $penalties[0]);

        $repository = new PenaltyRepository($penalties[0]);
        $repository->save();

        $this->assertTrue(PenaltyRepository::member_have_unpaid_penalty($this->memberId));
        DB::table('penalties')->where('id', $this->penaltyId)->update(['paid_at' => '2026-11-11']);
        $this->assertFalse(PenaltyRepository::member_have_unpaid_penalty($this->memberId));

        $this->expectException(MemberNotExistsException::class);
        PenaltyRepository::member_have_unpaid_penalty(999999);
    }

    public function test_penalty_repository_marks_delayed_borrows_as_existing_or_new(): void
    {
        $delayed = [
            ['borrowed_id' => $this->borrowId, 'member_id' => $this->memberId],
            ['borrowed_id' => 999999, 'member_id' => $this->memberId],
        ];

        $result = PenaltyRepository::penalties_exists($delayed);

        $this->assertTrue($result[0]['exists']);
        $this->assertFalse($result[1]['exists']);
    }

    public function test_penalty_repository_updates_old_penalty_amount_and_reports_count(): void
    {
        $result = PenaltyRepository::update_penalties_detail();

        $this->assertSame(['updated_penalty' => 1], $result);
        $this->assertDatabaseMissing('penalties', [
            'id' => $this->penaltyId,
            'amount' => 100,
            'calculated_at' => '2000-01-01',
        ]);

        $this->assertSame(['updated_penalty' => 0], PenaltyRepository::update_penalties_detail());
    }

    public function test_borrow_repository_constructor_and_delayed_borrows_calculation(): void
    {
        $entity = new BorrowEntity(
            $this->borrowId,
            $this->memberId,
            $this->bookId,
            new Date('2000-01-01'),
            null,
            null,
            BorrowStatus::borrowed,
            0
        );

        $repository = new BorrowRepository($entity);
        $this->assertInstanceOf(BorrowRepository::class, $repository);

        DB::table('borrows')->insert([
            'member_id' => $this->memberId,
            'book_id' => $this->bookId,
            'borrowed_at' => Date::now()->get(),
            'due_date' => null,
            'returned_at' => null,
            'status' => 'borrowed',
            'penalty_amount' => 0,
        ]);

        $delayed = BorrowRepository::delayed_borrows();

        $this->assertCount(1, $delayed);
        $this->assertSame($this->borrowId, $delayed[0]['borrowed_id']);
        $this->assertSame($this->memberId, $delayed[0]['member_id']);
        $this->assertSame(
            Date::difference(new Date('2000-01-01'), Date::now()) * BorrowServices::PENALTY_AMOUNT_PER_DAY,
            $delayed[0]['amount']
        );
    }

    public function test_repository_save_accepts_existing_special_value_objects(): void
    {
        $member = new MemberEntity(
            $this->memberId,
            'Test Member',
            new Email('member@example.com'),
            'hashed-password',
            new Phone('+491234567890'),
            'Test address',
            new Date('2020-01-01'),
            true,
            0
        );

        $repository = new MemberRepository($member);
        $repository->update(['address' => 'Updated address']);
        $repository->save();

        $this->assertDatabaseHas('members', [
            'id' => $this->memberId,
            'address' => 'Updated address',
        ]);
    }

    public function test_borrow_repository_saves_entity_with_enum_and_date_value_objects(): void
    {
        $entity = new BorrowEntity(
            $this->borrowId,
            $this->memberId,
            $this->bookId,
            new Date('2000-01-01'),
            null,
            null,
            BorrowStatus::returned,
            250
        );

        $repository = new BorrowRepository($entity);
        $repository->save();

        $this->assertDatabaseHas('borrows', [
            'id' => $this->borrowId,
            'status' => 'returned',
            'penalty_amount' => 250,
            'borrowed_at' => '2000-01-01',
        ]);
    }

    public function test_borrow_repository_search_returns_borrow_entity(): void
    {
        $borrows = BorrowRepository::search([['id', '=', $this->borrowId]]);

        $this->assertCount(1, $borrows);
        $this->assertInstanceOf(BorrowEntity::class, $borrows[0]);
        $this->assertSame($this->borrowId, $borrows[0]->get()['id']);
    }

    public function test_member_repository_updates_special_value_object_attributes(): void
    {
        $member = new MemberEntity(
            $this->memberId,
            'Test Member',
            new Email('member@example.com'),
            'hashed-password',
            new Phone('+491234567890'),
            'Test address',
            new Date('2020-01-01'),
            true,
            0
        );

        $repository = new MemberRepository($member);
        $repository->update([
            'email' => 'updated@example.com',
            'phone' => '+491234567892',
            'membership_date' => '2021-02-03',
        ]);
        $repository->save();

        $this->assertDatabaseHas('members', [
            'id' => $this->memberId,
            'email' => 'updated@example.com',
            'phone' => '+491234567892',
            'membership_date' => '2021-02-03',
        ]);
    }

    public function test_penalty_repository_throws_when_member_does_not_exist(): void
    {
        $this->expectException(MemberNotExistsException::class);

        PenaltyRepository::member_have_unpaid_penalty(999999);
    }

    public function test_repository_creates_new_model_when_entity_id_does_not_exist(): void
    {
        $repository = new CategoryRepository(
            new CategoryEntity(999999, 'New after missing id', 'New category')
        );

        $repository->save();

        $this->assertDatabaseHas('categories', [
            'name' => 'New after missing id',
        ]);
    }

    public function test_repository_conversion_requires_a_model_or_attributes_array(): void
    {
        $method = new \ReflectionMethod(\App\Repositories\Repository::class, 'convert_db_format_2_type_class');

        $this->expectException(\\InvalidArgumentException::class);
        $model = null;
        $attributes = null;
        $method->invokeArgs(null, [&$model, &$attributes]);
    }

    public function test_repository_attribute_conversion_falls_back_for_backed_enums(): void
    {
        $method = new \\ReflectionMethod(BorrowRepository::class, 'convert_db_format_2_type_class');
        $attributes = ['status' => 'returned'];

        $method->invokeArgs(null, [null, &$attributes]);

        $this->assertSame('returned', $attributes['status']);
    }
}
