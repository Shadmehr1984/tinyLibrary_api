<?php

namespace App\Services;

use App\Domain\Builders\BookBuilder;
use App\Domain\Entities\Book;
use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\ISBN;
use App\Http\Requests\BookDeleteRequest;
use App\Http\Requests\BookRequest;
use App\Http\Requests\BookSearchRequest;
use App\Http\Requests\BookUpdateRequest;
use App\Repositories\BookRepository;

class BookServices
{
    const SEARCH_REQUEST_ATTRIBUTES = [
        'title' => ['column' => 'title', 'operator' => '='],
        'author' => ['column' => 'author', 'operator' => '='],
        'isbn' => ['column' => 'isbn', 'operator' => '='],
        'published' => ['column' => 'published', 'operator' => '='],
        'published_before' => ['column' => 'published', 'operator' => '<'],
        'published_after' => ['column' => 'published', 'operator' => '>'],
        'publisher' => ['column' => 'publisher', 'operator' => '='],
        'category_id' => ['column' => 'category_id', 'operator' => '='],
        'total_copies' => ['column' => 'total_copies', 'operator' => '='],
        'total_copies_lower_than' => ['column' => 'total_copies', 'operator' => '<'],
        'total_copies_greater_than' => ['column' => 'total_copies', 'operator' => '>'],
        'available_copies' => ['column' => 'available_copies', 'operator' => '='],
        'available_copies_lower_than' => ['column' => 'available_copies', 'operator' => '<'],
        'available_copies_greater_than' => ['column' => 'available_copies', 'operator' => '>'],
        'description' => ['column' => 'description', 'operator' => 'LIKE'],
        'location' => ['column' => 'location', 'operator' => '='],
    ];

    const UPDATE_REQUEST_ATTRIBUTES = [
        'title',
        'author',
        'isbn',
        'published',
        'publisher',
        'category_id',
        'total_copies',
        'description',
        'location',
    ];

    private static function convert_request_to_entity(BookRequest $request): Book
    {
        $builder = new BookBuilder();

        $entity = $builder->set_id(null)->set_title($request->title)->set_author($request->author)->set_isbn(new ISBN($request->isbn))->set_published(new Date($request->published))->set_publisher($request->publisher)->set_category_id($request->category_id)->set_total_copies($request->total_copies)->set_available_copies($request->total_copies)-> //! remember this 
            set_description($request->description)->set_location($request->location)->set_deleted_at(null)->build();

        return $entity;
    }

    private static function take_update_attributes(BookUpdateRequest $request): array
    {
        $attributes = [];
        
        foreach (static::UPDATE_REQUEST_ATTRIBUTES as $attribute) {
            if ($request->$attribute != null){
                $attributes[$attribute] = $request->$attribute;
            }
        }

        return $attribute;
    }

    public static function add(BookRequest $request)
    {
        $entity = BookServices::convert_request_to_entity($request);

        $repository = new BookRepository($entity);
        $repository->save();
    }

    public static function delete(BookDeleteRequest $request)
    {
        $entity = BookRepository::search([['isbn', '=', $request->isbn->__toString()]])[0];

        $repository = new BookRepository($entity);

        $repository->update(['deleted_at' => Date::now()]);

        $repository->save();
    }

    public static function update(BookUpdateRequest $request)
    {
        $entity = BookRepository::search([['isbn', '=', $request->target_isbn->__toString()]])[0];

        $repository = new BookRepository($entity);

        $attributes = static::take_update_attributes($request);

        $repository->update($attributes);

        $repository->save();
    }

    public static function search(BookSearchRequest $request)
    {
        $attributes = [];

        foreach (static::SEARCH_REQUEST_ATTRIBUTES as $attribute => $search_detail) {
            if ($request->$attribute == null) continue;
            $attributes[] = [$search_detail['column'], $search_detail['operator'], $request->$attribute];
        }

        $entities = BookRepository::search($attributes);

        return $entities;
    }
}
