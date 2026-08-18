<?php

use App\Http\Requests\BookSearchRequest;
use App\Repositories\BookRepository;
use App\Services\BookServices;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//tests
Route::get('/test', function (){
    $request = new BookSearchRequest();
    $result = BookServices::search($request);
    $data = [];
    foreach ($result as $value) {
        $data[] = $value->get();
    }
    return response()->Json([
        'ki' => $data[0],
        'goz' => $request
    ]);
});
