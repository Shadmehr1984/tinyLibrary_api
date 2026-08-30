<?php

use App\Domain\ValueObjects\ISBN;
use App\Http\Controllers\LibrarianController;
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

// librarian controller
Route::post('/v1/add_book', [LibrarianController::class, 'add_book'])->middleware('auth:librarian');

Route::delete('/v1/delete_book', [LibrarianController::class, 'delete_book'])->middleware('auth:librarian');
//!
Route::post('/v1/search_book', [LibrarianController::class, 'search_book'])->middleware('auth:librarian');
//!
Route::put('/v1/update_book', [LibrarianController::class, 'update_book'])->middleware('auth:librarian');

Route::post('/v1/add_member', [LibrarianController::class, 'add_member'])->middleware('auth:librarian');

Route::put('/v1/deactivate_member', [LibrarianController::class, 'deactivate_member'])->middleware('auth:librarian');
//!
Route::put('/v1/activate_member', [LibrarianController::class, 'activate_member'])->middleware('auth:librarian');
//!
Route::delete('/v1/delete_member', [LibrarianController::class, 'delete_member'])->middleware('auth:librarian');
//!
Route::post('/v1/search_member', [LibrarianController::class, 'search_member'])->middleware('auth:librarian');
//!
Route::put('/v1/update_member', [LibrarianController::class, 'update_member'])->middleware('auth:librarian');
//!
Route::post('/v1/add_category', [LibrarianController::class, 'add_category'])->middleware('auth:librarian');