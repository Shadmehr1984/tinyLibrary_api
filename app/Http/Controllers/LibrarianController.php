<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookDeleteRequest;
use App\Http\Requests\BookRequest;
use App\Http\Requests\BookSearchRequest;
use App\Http\Requests\BookUpdateRequest;
use App\Http\Requests\CategoryDeleteRequest;
use App\Http\Requests\CategoryRequest;
use App\Http\Requests\CategorySearchRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Http\Requests\MemberActivateRequest;
use App\Http\Requests\MemberDeactivateRequest;
use App\Http\Requests\MemberDeleteRequest;
use App\Http\Requests\MemberRequest;
use App\Http\Requests\MemberSearchRequest;
use App\Http\Requests\MemberUpdateRequest;
use App\Services\BookServices;
use App\Services\CategoryServices;
use App\Services\MemberServices;
use Illuminate\Http\Request;

class LibrarianController extends Controller
{
    public function add_book(BookRequest $request){
        BookServices::add($request);

        return response()->json([
            'status-code' => 201,
            'message' => 'book created'
        ]);
    }

    public function delete_book(BookDeleteRequest $request){
        BookServices::delete($request);

        return response()->json([
            'status-code' => 200,
            'message' => 'book deleted'
        ]);
    }

    public function search_book(BookSearchRequest $request){
        $book_entities = BookServices::search($request);

        $books = [];

        foreach ($book_entities as $entity) {
            $books[] = $entity->get();
        }

        return response()->json([
            'status-code' => 200,
            'books' => $books
        ]);
    }

    public function update_book(BookUpdateRequest $request){
        BookServices::update($request);

        return response()->json([
            'status-code' => 200,
            'message' => 'book updated'
        ]);
    }

    public function add_member(MemberRequest $request){
        MemberServices::add($request);

        return response()->json([
            'status-code' => 201,
            'message' => 'member created'
        ]);
    }

    public function deactivate_member(MemberDeactivateRequest $request){
        MemberServices::deactivate($request);

        return response()->json([
            'status-code' => 200,
            'message' => 'member deactivated'
        ]);
    }

    public function activate_member(MemberActivateRequest $request){
        MemberServices::activate($request);

        return response()->json([
            'status-code' => 200,
            'message' => 'member activated'
        ]);
    }

    public function delete_member(MemberDeleteRequest $request){
        MemberServices::delete($request);

        return response()->json([
            'status-code' => 200,
            'message' => 'member deleted'
        ]);
    }

    public function search_member(MemberSearchRequest $request){
        $member_entities = MemberServices::search($request);

        $members = [];

        foreach ($member_entities as $entity) {
            $members[] = $entity->get();
        }

        return response()->json([
            'status-code' => 200,
            'members' => $members
        ]);
    }

    public function update_member(MemberUpdateRequest $request){
        MemberServices::update($request);

        return response()->json([
            'status-code' => 200,
            'message' => 'member updated'
        ]);
    }

    public function add_category(CategoryRequest $request){
        CategoryServices::add($request);

        return response()->json([
            'status-code' => 201,
            'message' => 'category created'
        ]);
    }

    public function delete_category(CategoryDeleteRequest $request){
        CategoryServices::delete($request);

        return response()->json([
            'status-code' => 200,
            'message' => 'category deleted'
        ]);
    }

    public function search_category(CategorySearchRequest $request){
        $category_entities = CategoryServices::search($request);

        $categories = [];

        foreach ($category_entities as $entity) {
            $categories[] = $entity->get();
        }

        return response()->json([
            'status-code' => 200,
            'categories' => $categories
        ]);
    }

    public function update_category(CategoryUpdateRequest $request){
        CategoryServices::update($request);

        return response()->json([
            'status-code' => 200,
            'message' => 'category updated'
        ]);
    }
}
