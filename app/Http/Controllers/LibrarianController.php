<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookDeleteRequest;
use App\Http\Requests\BookRequest;
use App\Http\Requests\BookSearchRequest;
use App\Http\Requests\CategoryRequest;
use App\Http\Requests\MemberActivateRequest;
use App\Http\Requests\MemberDeactivateRequest;
use App\Http\Requests\MemberRequest;
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
            'books' => $books
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

    public function add_category(CategoryRequest $request){
        CategoryServices::add($request);

        return response()->json([
            'status-code' => 201,
            'message' => 'category created'
        ]);
    }
}
