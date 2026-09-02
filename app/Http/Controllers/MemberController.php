<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberLoginRequest;
use App\Models\Member;
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
            'massage' => 'successful login, welcome ' . $member->name,
            'member' => $member,
            'token' => $token
        ]);
    }

    public function logout(){
        try {
            $member = (Member::class)(auth()->user());
        } catch (\Throwable $th) {
            $member = auth()->user();
        }

        $member->currentAccessToken()->delete();

        return response()->json([
            'message' => "successful logout"
        ]);
    }
}
