<?php

namespace App\Services;

use App\Domain\Builders\PenaltyBuilder;
use App\Domain\Entities\penalty;
use App\Domain\ValueObjects\Date;
use App\Http\Requests\PenaltyDeleteRequest;
use App\Http\Requests\PenaltyRequest;
use App\Http\Requests\PenaltySearchRequest;
use App\Http\Requests\PenaltyUpdateRequest;
use App\Repositories\PenaltyRepository;

class PenaltyServices
{
    private const SEARCH_REQUEST_ATTRIBUTES = [
        'borrowed_id' => ['column' => 'borrowed_id', 'operator' => '='],
        'member_id' => ['column' => 'member_id', 'operator' => '='],
        'amount' => ['column' => 'amount', 'operator' => '='],
        'amount_lower_than' => ['column' => 'amount', 'operator' => '<'],
        'amount_greater_than' => ['column' => 'amount', 'operator' => '>'],
        'calculated_at' => ['column' => 'calculated_at', 'operator' => '='],
        'calculated_before_at' => ['column' => 'calculated_at', 'operator' => '<'],
        'calculated_after_at' => ['column' => 'calculated_at', 'operator' => '>'],
        'paid_at' => ['column' => 'paid_at', 'operator' => '='],
        'paid_before_at' => ['column' => 'paid_at', 'operator' => '<'],
        'paid_after_at' => ['column' => 'paid_at', 'operator' => '>'],
    ];

    private const UPDATE_REQUEST_ATTRIBUTES = [
        'paid_at'
    ];

    private static function convert_request_to_entity(PenaltyRequest $request): penalty
    {
        $builder = new PenaltyBuilder();

        $entity = $builder->set_id(null)->set_amount($request->amount)->set_borrowed_id($request->borrowed_id)->set_calculated_at(Date::now())->set_member_id($request->member_id)->set_paid_at(null)->build();

        return $entity;
    }

    private static function take_update_attributes(PenaltyUpdateRequest $request): array
    {
        $attributes = [];
        
        foreach (static::UPDATE_REQUEST_ATTRIBUTES as $attribute) {
            if ($request->$attribute != null){
                $attributes[$attribute] = $request->$attribute;
            }
        }

        return $attributes;
    }

    public static function add(PenaltyRequest $request)
    {
        $entity = PenaltyServices::convert_request_to_entity($request);

        $repository = new PenaltyRepository($entity);
        $repository->save();
    }

    public static function delete(PenaltyDeleteRequest $request)
    {
        $entity = PenaltyRepository::search([
            ['member_id', '=', $request->member_id],
            ['borrowed_id', '=', $request->borrowed_id]
        ])[0];

        $repository = new PenaltyRepository($entity);

        $repository->delete();
    }

    public static function update(PenaltyUpdateRequest $request)
    {
        $entity = PenaltyRepository::search([
            ['member_id', '=', $request->target_member_id],
            ['borrowed_id', '=', $request->target_borrowed_id]
        ])[0];

        $repository = new PenaltyRepository($entity);

        $attributes = static::take_update_attributes($request);

        $repository->update($attributes);

        $repository->save();
    }

    public static function search(PenaltySearchRequest $request)
    {
        $attributes = [];

        foreach (static::SEARCH_REQUEST_ATTRIBUTES as $attribute => $search_detail) {
            if ($request->$attribute == null) continue;
            $attributes[] = [$search_detail['column'], $search_detail['operator'], $request->$attribute];
        }

        $entities = PenaltyRepository::search($attributes, $request->limit);

        return $entities;
    }
}
