<?php

namespace Tests\Feature\Repositories;

use App\Domain\Entities\Book as BookEntity;
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
}
