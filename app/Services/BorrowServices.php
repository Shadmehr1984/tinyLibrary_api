<?php

namespace App\Services;

use App\Domain\Builders\BorrowBuilder;
use App\Domain\Entities\Borrow;
use App\Domain\ValueObjects\BorrowStatus;
use App\Domain\ValueObjects\Date;
use App\Http\Requests\BorrowDeleteRequest;
use App\Http\Requests\BorrowRequest;
use App\Http\Requests\BorrowSearchRequest;
use App\Http\Requests\BorrowUpdateRequest;
use App\Repositories\BorrowRepository;

class BorrowServices
{
    private const SEARCH_REQUEST_ATTRIBUTES = [
        'member_id' => ['column' => 'member_id', 'operator' => '='],
        'book_id' => ['column' => 'book_id', 'operator' => '='],
        'borrowed_at' => ['column' => 'borrowed_at', 'operator' => '='],
        'borrowed_before_at' => ['column' => 'borrowed_at', 'operator' => '<'],
        'borrowed_after_at' => ['column' => 'borrowed_at', 'operator' => '>'],
        'due_date' => ['column' => 'due_date', 'operator' => '='],
        'due_date_before' => ['column' => 'due_date', 'operator' => '<'],
        'due_date_after' => ['column' => 'due_date', 'operator' => '>'],
        'returned_at' => ['column' => 'returned_at', 'operator' => '='],
        'returned_before_at' => ['column' => 'returned_at', 'operator' => '<'],
        'returned_after_at' => ['column' => 'returned_at', 'operator' => '>'],
        'status' => ['column' => 'status', 'operator' => '='],
        'penalty_amount' => ['column' => 'penalty_amount', 'operator' => '='],
        'penalty_amount_lower_than' => ['column' => 'penalty_amount', 'operator' => '<'],
        'penalty_amount_greater_than' => ['column' => 'penalty_amount', 'operator' => '>'],
    ];

    private const UPDATE_REQUEST_ATTRIBUTES = [
        'due_date',
            'returned_at',
            'status',
            'penalty_amount'
    ];

    private static function convert_request_to_entity(BorrowRequest $request): Borrow
    {
        $builder = new BorrowBuilder();

        $entity = $builder->set_id(null)->set_book_id($request->book_id)->set_borrowed_at(new Date($request->borrowed_at))->set_due_date(null)->set_member_id($request->member_id)->set_penalty_amount(0)->set_returned_at(null)->set_status(BorrowStatus::borrowed)->build();

        return $entity;
    }

    private static function take_update_attributes(BorrowUpdateRequest $request): array
    {
        $attributes = [];
        
        foreach (static::UPDATE_REQUEST_ATTRIBUTES as $attribute) {
            if ($request->$attribute != null){
                $attributes[$attribute] = $request->$attribute;
            }
        }

        return $attribute;
    }

    public static function add(BorrowRequest $request)
    {
        $entity = BorrowServices::convert_request_to_entity($request);

        $repository = new BorrowRepository($entity);
        $repository->save();
    }

    public static function delete(BorrowDeleteRequest $request)
    {
        $entity = BorrowRepository::search([
            ['member_id', '=', $request->member_id],
            ['book_id', '=', $request->book_id],
            ['borrowed_at', '=', $request->borrowed_at]
        ])[0];

        $repository = new BorrowRepository($entity);

        $repository->delete();
    }

    public static function update(BorrowUpdateRequest $request)
    {
        $entity = BorrowRepository::search([
            ['target_member_id', '=', $request->member_id],
            ['target_book_id', '=', $request->book_id],
            ['target_borrowed_at', '=', $request->borrowed_at]
        ])[0];

        $repository = new BorrowRepository($entity);

        $attributes = static::take_update_attributes($request);

        $repository->update($attributes);

        $repository->save();
    }

    public static function search(BorrowSearchRequest $request)
    {
        $attributes = [];

        foreach (static::SEARCH_REQUEST_ATTRIBUTES as $attribute => $search_detail) {
            if ($request->$attribute == null) continue;
            $attributes[] = [$search_detail['column'], $search_detail['operator'], $request->$attribute];
        }

        $entities = BorrowRepository::search($attributes);

        return $entities;
    }
}
