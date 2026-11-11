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
use App\Http\Requests\LibrarianLoginRequest;
use App\Http\Requests\MemberActivateRequest;
use App\Http\Requests\MemberDeactivateRequest;
use App\Http\Requests\MemberDeleteRequest;
use App\Http\Requests\MemberRequest;
use App\Http\Requests\MemberSearchRequest;
use App\Http\Requests\MemberUpdateRequest;
use App\Http\Requests\PenaltyPayRequest;
use App\Models\Librarian;
use App\Services\BookCacheServices;
use App\Services\BookServices;
use App\Services\CategoryServices;
use App\Services\exceptions\MaxCacheObjReach;
use App\Services\exceptions\PenaltyAlreadyPaidException;
use App\Services\MemberServices;
use App\Services\PenaltyServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PharIo\Manifest\Library;

class LibrarianController extends Controller
{
    public function login(LibrarianLoginRequest $request)
    {
        $librarian = Librarian::all()->where('email', '=', $request->email)->first();
        if (!$librarian || !Hash::check($request->password, $librarian->password)) {
            return response()->json([
                'massage' => 'invalid email or password'
            ], 401);
        }

        $token = $librarian->createToken('librarian-token')->plainTextToken;

        return response()->json([
            'massage' => 'successful login, welcome ' . $librarian->name,
            'librarian' => $librarian,
            'token' => $token
        ], 200);
    }

    public function logout()
    {
        try {
            $librarian = (Librarian::class)(auth()->user());
        } catch (\Throwable $th) {
            $librarian = auth()->user();
        }
        $librarian->currentAccessToken()->delete();

        return response()->json([
            'message' => 'successful logout'
        ], 200);
    }

    public function add_book(BookRequest $request)
    {
        BookServices::add($request);

        return response()->json([
            'message' => 'book created'
        ], 201);
    }

    public function delete_book(BookDeleteRequest $request)
    {
        BookServices::delete($request);

        return response()->json([
            'message' => 'book deleted'
        ], 200);
    }

    public function search_book(BookSearchRequest $request)
    {
        $books = [];

        $cached_books = BookCacheServices::get($request);

        if (isset($cached_books)) {
            $books = $cached_books;
        } else {
            $entities = BookServices::search($request);

            try {
                BookCacheServices::cache($entities);
            } catch (MaxCacheObjReach $e) {
                //cache is full
            }

            foreach ($entities as $entity) {
                $entity->pure_value(true);
                $books[] = $entity->get();
            }
        }

        return response()->json([
            'books' => $books
        ], 200);
    }

    public function update_book(BookUpdateRequest $request)
    {
        BookServices::update($request);

        return response()->json([
            'message' => 'book updated'
        ], 200);
    }

    public function add_member(MemberRequest $request)
    {
        MemberServices::add($request);

        return response()->json([
            'message' => 'member created'
        ], 201);
    }

    public function deactivate_member(MemberDeactivateRequest $request)
    {
        MemberServices::deactivate($request);

        return response()->json([
            'message' => 'member deactivated'
        ], 200);
    }

    public function activate_member(MemberActivateRequest $request)
    {
        MemberServices::activate($request);

        return response()->json([
            'message' => 'member activated'
        ], 200);
    }

    public function delete_member(MemberDeleteRequest $request)
    {
        MemberServices::delete($request);

        return response()->json([
            'message' => 'member deleted'
        ], 200);
    }

    public function search_member(MemberSearchRequest $request)
    {
        $member_entities = MemberServices::search($request);

        $members = [];

        foreach ($member_entities as $entity) {
            $entity->pure_value(true);
            $member = $entity->get();
            unset($member['password']);
            $members[] = $member;
        }

        return response()->json([
            'members' => $members
        ], 200);
    }

    public function update_member(MemberUpdateRequest $request)
    {
        MemberServices::update($request);

        return response()->json([
            'message' => 'member updated'
        ], 200);
    }

    public function add_category(CategoryRequest $request)
    {
        CategoryServices::add($request);

        return response()->json([
            'message' => 'category created'
        ], 201);
    }

    public function delete_category(CategoryDeleteRequest $request)
    {
        CategoryServices::delete($request);

        return response()->json([
            'message' => 'category deleted'
        ], 200);
    }

    public function search_category(CategorySearchRequest $request)
    {
        $category_entities = CategoryServices::search($request);

        $categories = [];

        foreach ($category_entities as $entity) {
            $categories[] = $entity->get();
        }

        return response()->json([
            'categories' => $categories
        ], 200);
    }

    public function update_category(CategoryUpdateRequest $request)
    {
        CategoryServices::update($request);

        return response()->json([
            'message' => 'category updated'
        ], 200);
    }

    public function pay_penalty(PenaltyPayRequest $request)
    {
        try {
            PenaltyServices::pay($request);
        } catch (PenaltyAlreadyPaidException $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 409);
        }

        return response()->json([
            'message' => 'penalty paid successfully'
        ], 200);
    }
}
