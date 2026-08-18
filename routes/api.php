<?php

use App\Domain\ValueObjects\ISBN;
use App\Http\Requests\BookRequest;
use App\Http\Requests\BookSearchRequest;
use App\Http\Requests\CategorySearchRequest;
use App\Http\Requests\LibrarianSearchRequest;
use App\Models\Book;
use App\Models\Librarian;
use App\Repositories\BookRepository;
use App\Rules\Isbn as RulesIsbn;
use App\Services\BookServices;
use App\Services\CategoryServices;
use App\Services\LibrarianServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//tests
Route::post('/test/login', function(Request $request){
    $librarian = Librarian::all()->where('email', '=', $request->email)->first();
    if (!$librarian || !Hash::check($request->password, $librarian->password)){
        return response()->json([
            'massage' => 'invalid email or password'
        ]);
    }

    $token = $librarian->createToken('librarian-token')->plainTextToken;

    return response()->json([
        'massage' => 'successful login, welcome '.$librarian->name,
        'librarian' => $librarian,
        'token' => $token
    ]);
});

Route::get('/test/book', function(){
    $data = Book::all();
    return response()->json([
        'books' => $data
    ]);
});

Route::post('/test', function(LibrarianSearchRequest $request){
    $result = LibrarianServices::search($request);

    $data = [];

    foreach ($result as $entity) {
        $data[] = $entity->get();
    }

    return response()->json([
        'data' => $data,
    ]);
})->middleware('auth:librarian');