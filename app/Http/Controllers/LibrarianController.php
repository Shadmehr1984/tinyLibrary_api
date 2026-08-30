<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookDeleteRequest;
use App\Http\Requests\BookRequest;
use App\Services\BookServices;
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
}
