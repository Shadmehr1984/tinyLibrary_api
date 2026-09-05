<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookSearchRequest;
use App\Http\Requests\BorrowRequest;
use App\Http\Requests\MemberLoginRequest;
use App\Models\Member;
use App\Services\BookServices;
use App\Services\BorrowServices;
use App\Services\MemberServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function login(MemberLoginRequest $request)
    {
        $member = Member::all()->where('email', '=', $request->email)->first();
        if (!$member || !Hash::check($request->password, $member->password)) {
            return response()->json([
                'massage' => 'invalid email or password'
            ]);
        }

        $token = $member->createToken('member-token')->plainTextToken;

        return response()->json([
            'status-code' => 200,
            'massage' => 'successful login, welcome ' . $member->name,
            'member' => $member,
            'token' => $token
        ]);
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
            'status-code' => 200,
            'message' => "successful logout"
        ]);
    }

    public function search_book(BookSearchRequest $request)
    {
        $entities = BookServices::search($request);

        $books = [];

        foreach ($entities as $entity) {
            $entity->pure_value(true);
            $books[] = $entity->get();
        }

        return response()->json([
            'status-code' => 200,
            'books' => $books
        ]);
    }

    public function add_borrow(BorrowRequest $request)
    {
        if (MemberServices::member_borrows($request->member_id) >= BorrowServices::MAX_BORROWS_LIMIT){
            return response()->json([
                'status-code' => 403,
                'message' => 'you reach max borrows limit'
            ]);
        }
        BorrowServices::add($request);

        return response()->json([
            'status-code' => 201,
            'message' => 'borrow book successful'
        ]);
    }
}
