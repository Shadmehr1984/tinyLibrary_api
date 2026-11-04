<?php

namespace App\Services;

use App\Domain\ValueObjects\ISBN;
use App\Http\Requests\BookSearchRequest;
use App\Repositories\CacheRepository;
use App\Services\exceptions\MaxCacheObjReach;

class BookCacheServices
{
    const EXPIRE_TIME = 180;
    const MAX_CACHE_OBJ_COUNT = 50;
    const NUMERIC_FIELDS = [
        'id',
        'category_id',
        'total_copies',
        'available_copies',
    ];
    const NULLABLE_FIELDS = [
        'deleted_at'
    ];

    public static function cache(array $books)
    {
        foreach ($books as $book) {
            $book->pure_value(true);
            $book_hash_set = $book->get();
            $book_cache_key = "book-" . $book_hash_set['isbn'] . "-" . $book_hash_set['title'];
            if (!CacheRepository::exists($book_cache_key)) {
                if (CacheRepository::obj_count() >= static::MAX_CACHE_OBJ_COUNT) {
                    throw new MaxCacheObjReach(static::MAX_CACHE_OBJ_COUNT);
                }
                #cache
                CacheRepository::set($book_cache_key, $book_hash_set, static::EXPIRE_TIME);
            }
        }
    }

    public static function get(BookSearchRequest $request)
    {
        if (empty($request->isbn) && empty($request->title)){
            return null;
        }

        $isbn = $request->isbn;
        $title = $request->title;

        $word = $isbn . isset($isbn) ? "-" : "" . $title;

        $book_keys = CacheRepository::search_keys($word);

        $books = [];

        foreach($book_keys as $key){
            $books[] = CacheRepository::get($key);
        }

        #fix numeric fields and fix nullable fields if they are null
        foreach ($books as &$book) {
            foreach (static::NUMERIC_FIELDS as $field) {
                $book[$field] = (int)($book[$field]);
            }
            foreach(static::NULLABLE_FIELDS as $field){
                if ($book[$field] == ""){
                    $book[$field] = null;
                }
            }
        }

        if (sizeof($books) == 0) return null;

        return $books;
    }
}
