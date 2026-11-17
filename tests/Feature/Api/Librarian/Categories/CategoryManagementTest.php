<?php

namespace Tests\Feature\Api\Librarian\Categories;

use Tests\Support\Api\ApiTestCase;
use Tests\Support\Factories\TestDataFactory;
use Tests\Support\Helpers\AuthenticatesApiUsers;

class CategoryManagementTest extends ApiTestCase
{
    use AuthenticatesApiUsers;

    public function test_librarian_can_add_a_category(): void
    {
        $this->authenticateAsLibrarian(TestDataFactory::librarian())
            ->postJson('/v1/librarian/add_category', [
                'name' => 'Computer Science',
                'description' => 'Books about computer science.',
            ])->assertCreated()
            ->assertJsonPath('message', 'category created');

        $this->assertDatabaseHas('categories', ['name' => 'Computer Science']);
    }

    public function test_librarian_can_update_and_search_a_category(): void
    {
        $librarian = TestDataFactory::librarian();
        $category = TestDataFactory::category([
            'name' => 'Original Category',
            'description' => 'Original category description.',
        ]);

        $this->authenticateAsLibrarian($librarian)
            ->putJson('/v1/librarian/update_category', [
                'target_id' => $category->id,
                'name' => 'Updated Category',
            ])->assertOk()
            ->assertJsonPath('message', 'category updated');

        $this->postJson('/v1/librarian/search_category', ['name' => 'Updated Category'])
            ->assertOk()
            ->assertJsonPath('categories.0.name', 'Updated Category');
    }

    public function test_librarian_can_delete_a_category_without_books(): void
    {
        $librarian = TestDataFactory::librarian();
        $category = TestDataFactory::category();

        $this->authenticateAsLibrarian($librarian)
            ->deleteJson('/v1/librarian/delete_category', ['id' => $category->id])
            ->assertOk()
            ->assertJsonPath('message', 'category deleted');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_search_rejects_a_request_without_any_search_criteria(): void
    {
        $this->authenticateAsLibrarian(TestDataFactory::librarian())
            ->postJson('/v1/librarian/search_category', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['_check']);
    }

    public function test_add_category_rejects_a_duplicate_name(): void
    {
        TestDataFactory::category(['name' => 'Duplicate Category']);
        
        $this->authenticateAsLibrarian(TestDataFactory::librarian())
            ->postJson('/v1/librarian/add_category', [
                'name' => 'Duplicate Category',
                'description' => 'Another description for the same category.',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

}
