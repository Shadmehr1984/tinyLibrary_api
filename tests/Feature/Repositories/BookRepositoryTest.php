<?php

namespace Tests\Feature\Repositories;

use App\Domain\Entities\Book as BookEntity;
use App\Models\Book;
use App\Models\Category;
use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\ISBN;
use App\Repositories\BookRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Programming',
            'description' => 'Books about software development.',
        ]);

        DB::table('books')->insert([
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'isbn' => '9780132350884',
            'published' => '2008-08-01',
            'publisher' => 'Prentice Hall',
            'category_id' => $categoryId,
            'total_copies' => 5,
            'available_copies' => 3,
            'description' => 'A handbook of agile software craftsmanship.',
            'location' => 'A-12',
            'deleted_at' => null,
        ]);
    }

    public function test_search_returns_matching_book_entities(): void
    {
        $books = BookRepository::search([
            ['title', '=', 'Clean Code'],
        ]);

        $this->assertCount(1, $books);
        $this->assertInstanceOf(BookEntity::class, $books[0]);

        $attributes = $books[0]->get();

        $this->assertSame('Clean Code', $attributes['title']);
        $this->assertSame('Robert C. Martin', $attributes['author']);
        $this->assertInstanceOf(ISBN::class, $attributes['isbn']);
        $this->assertSame('9780132350884', $attributes['isbn']->get());
        $this->assertInstanceOf(Date::class, $attributes['published']);
        $this->assertSame('2008-08-01', $attributes['published']->get());
        $this->assertNull($attributes['deleted_at']);
    }

    public function test_search_returns_an_empty_array_when_no_book_matches(): void
    {
        $books = BookRepository::search([
            ['title', '=', 'A book that does not exist'],
        ]);

        $this->assertSame([], $books);
    }

    public function test_change_book_title(): void
    {
        $books = BookRepository::search([['title', '=', 'Clean Code']]);

        $book_entity = $books[0];

        $book_entity->set(
            [
                'title' => 'Dirty Code'
            ]
        );

        $repo = new BookRepository($book_entity);

        $repo->save();

        $books = BookRepository::search([['title', '=', 'Dirty Code']]);

        $book_entity = $books[0];

        $attributes = $book_entity->get();

        $this->assertSame('Dirty Code', $attributes['title']);
        $this->assertSame('Robert C. Martin', $attributes['author']);
        $this->assertInstanceOf(ISBN::class, $attributes['isbn']);
        $this->assertSame('9780132350884', $attributes['isbn']->get());
        $this->assertInstanceOf(Date::class, $attributes['published']);
        $this->assertSame('2008-08-01', $attributes['published']->get());
        $this->assertNull($attributes['deleted_at']);
    }

    public function test_delete_book(): void
    {
        $books = BookRepository::search([['title', '=', 'Clean Code']]);

        $book_entity = $books[0];

        $repo = new BookRepository($book_entity);

        $repo->delete();

        $this->assertDatabaseMissing('books', [
            'title' => 'Dirty Code'
        ]);

        $books = BookRepository::search([
            ['title', '=', 'Clean Code'],
        ]);

        $this->assertSame([], $books);
    }

    public function test_database_books_count_increases_by_adding_another_book(): void
    {
        $expected_books_count = 1;

        $this->assertDatabaseCount('books', $expected_books_count);

        DB::table('books')->insert([
            'title' => 'Dirty Code',
            'author' => 'Robert C. Martin',
            'isbn' => '9880132350884',
            'published' => '2008-08-01',
            'publisher' => 'Prentice Hall',
            'category_id' => 1,
            'total_copies' => 6,
            'available_copies' => 3,
            'description' => 'A handbook of agile software craftsmanship.',
            'location' => 'A-12',
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('books', [
            'isbn' => '9880132350884',
            'title' => 'Dirty Code'
        ]);

        $expected_books_count = 2;

        $this->assertDatabaseCount('books', $expected_books_count);
    }

    public function test_add_fake_category_and_book_and_count_must_increases(): void
    {
        $category = Category::factory()->create();

        $book = Book::factory()->create(
            [
                'category_id' => $category->id
            ]
        );

        $this->assertDatabaseCount('books', 2);
    }

    public function test_add_unavailable_book(): void
    {
        $category = Category::factory()->create();

        $unavailable_book = Book::factory()->unavailable()->create([
            'category_id' => $category->id
        ]);

        $this->assertDatabaseHas('books', [
            'isbn' => $unavailable_book->isbn,
            'available_copies' => 0
        ]);
    }

    public function test_add_fake_book_using_relations(): void
    {
        $book = Book::factory()->for(Category::factory())->create();

        $this->assertModelExists($book->category);

        $this->assertDatabaseHas('books', [
            'isbn' => $book->isbn
        ]);
    }

    public function test_add_category_with_three_book_relate_to_it_with_factory(): void
    {
        $category = Category::factory()->has(Book::factory()->count(3))->create();

        $this->assertCount(3, $category->books);

        foreach($category->books as $book){
            $this->assertDatabaseHas('books', [
                'isbn' => $book->isbn,
                'category_id' => $category->id
            ]);
        }
    }
}
