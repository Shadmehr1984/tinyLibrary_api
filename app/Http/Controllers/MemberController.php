<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookSearchRequest;
use App\Http\Requests\BorrowBackRequest;
use App\Http\Requests\BorrowRequest;
use App\Http\Requests\BorrowSearchRequest;
use App\Http\Requests\MemberLoginRequest;
use App\Http\Requests\PenaltySearchRequest;
use App\Models\Member;
use App\Services\BookCacheServices;
use App\Services\BookServices;
use App\Services\BorrowServices;
use App\Services\exceptions\MaxCacheObjReach;
use App\Services\MemberServices;
use App\Services\PenaltyServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function login(MemberLoginRequest $request)
    {
        $member = Member::where('email', '=', $request->email)->first();
        if (!$member || !Hash::check($request->password, $member->password) || !MemberServices::is_active($request->email)) {
            return response()->json([
                'message' => 'invalid email or password'
            ], 401);
        }

        $token = $member->createToken('member-token')->plainTextToken;

        return response()->json([
            'massage' => 'successful login, welcome ' . $member->name,
            'member' => $member,
            'token' => $token
        ], 200);
    }

    public function logout()
    {
        try {
            $member = (Member::class)(auth()->user());
        } catch (\Throwable $th) {
            $member = auth()->user();
        }

        $member->currentAccessToken()->delete();

        return response()->json([
            'message' => "successful logout"
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

    public function add_borrow(BorrowRequest $request)
    {
        $request->member_id = $request->user()->id;

        if (PenaltyServices::member_have_unpaid_penalty($request->member_id)) {
            return response()->json([
                'message' => 'you have unpaid penalties, first paid your penalties'
            ], 403);
        }

        if (PenaltyServices::member_reach_borrow_limit($request->member_id)) {
            return response()->json([
                'message' => 'you reach max borrows limit'
            ], 409);
        }

        if (!BookServices::is_available($request->book_id)) {
            return response()->json([
                'message' => 'book is not available'
            ], 409);
        }

        BorrowServices::add($request);

        return response()->json([
            'message' => 'borrow book successful'
        ], 201);
    }

    public function search_borrow(BorrowSearchRequest $request)
    {
        $request->member_id = $request->user()->id;

        $entities = BorrowServices::search($request);

        $borrows = [];

        foreach ($entities as $entity) {
            $entity->pure_value(true);
            $borrows[] = $entity->get();
        }

        return response()->json([
            'borrows' => $borrows
        ], 200);
    }

    public function back_borrow(BorrowBackRequest $request)
    {
        if (PenaltyServices::member_have_unpaid_penalty($request->user()->id)) {
            return response()->json([
                'message' => 'you have unpaid penalties, first paid your penalties'
            ], 403);
        }

        BorrowServices::back($request);

        return response()->json([
            'message' => 'book back'
        ], 200);
    }

    public function search_penalty(PenaltySearchRequest $request)
    {
        $request->member_id = $request->user()->id;

        $entities = PenaltyServices::search($request);

        $penalties = [];

        foreach ($entities as $entity) {
            $entity->pure_value(true);
            $penalties[] = $entity->get();
        }

        return response()->json([
            'penalties' => $penalties
        ], 200);
    }
}
