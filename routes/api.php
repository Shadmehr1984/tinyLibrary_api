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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

//tests

Route::get('/test', function(Request $request){

})->middleware('auth:librarian');

// librarian controller
Route::post('/v1/librarian/login', [LibrarianController::class, 'login']);

Route::post('/v1/librarian/logout', [LibrarianController::class, 'logout'])->middleware('auth:librarian');

Route::post('/v1/librarian/add_book', [LibrarianController::class, 'add_book'])->middleware('auth:librarian');

Route::delete('/v1/librarian/delete_book', [LibrarianController::class, 'delete_book'])->middleware('auth:librarian');

Route::post('/v1/librarian/search_book', [LibrarianController::class, 'search_book'])->middleware('auth:librarian');

Route::put('/v1/librarian/update_book', [LibrarianController::class, 'update_book'])->middleware('auth:librarian');

Route::post('/v1/librarian/add_member', [LibrarianController::class, 'add_member'])->middleware('auth:librarian');

Route::put('/v1/librarian/deactivate_member', [LibrarianController::class, 'deactivate_member'])->middleware('auth:librarian');

Route::put('/v1/librarian/activate_member', [LibrarianController::class, 'activate_member'])->middleware('auth:librarian');

Route::delete('/v1/librarian/delete_member', [LibrarianController::class, 'delete_member'])->middleware('auth:librarian');

Route::post('/v1/librarian/search_member', [LibrarianController::class, 'search_member'])->middleware('auth:librarian');

Route::put('/v1/librarian/update_member', [LibrarianController::class, 'update_member'])->middleware('auth:librarian');

Route::post('/v1/librarian/add_category', [LibrarianController::class, 'add_category'])->middleware('auth:librarian');

Route::delete('/v1/librarian/delete_category', [LibrarianController::class, 'delete_category'])->middleware('auth:librarian');

Route::post('/v1/librarian/search_category', [LibrarianController::class, 'search_category'])->middleware('auth:librarian');

Route::put('/v1/librarian/update_category', [LibrarianController::class, 'update_category'])->middleware('auth:librarian');

// member controller