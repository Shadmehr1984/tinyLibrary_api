<?php

namespace App\Services;

use App\Domain\Builders\MemberBuilder;
use App\Domain\Entities\Member;
use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Phone;
use App\Http\Requests\MemberDeleteRequest;
use App\Http\Requests\MemberRequest;
use App\Http\Requests\MemberSearchRequest;
use App\Http\Requests\MemberUpdateRequest;
use App\Repositories\MemberRepository;
use Illuminate\Support\Facades\Hash;

class MemberServices
{
    private const SEARCH_REQUEST_ATTRIBUTES = [
        'name' => ['column' => 'name', 'operator' => '='],
        'email' => ['column' => 'email', 'operator' => '='],
        'phone' => ['column' => 'phone', 'operator' => '='],
        'address' => ['column' => 'address', 'operator' => '='],
        'membership_date' => ['column' => 'membership_date', 'operator' => '='],
        'membership_date_before' => ['column' => 'membership_date', 'operator' => '<'],
        'membership_date_after' => ['column' => 'membership_date', 'operator' => '>'],
        'active' => ['column' => 'active', 'operator' => '='],
        'penalty_balance' => ['column' => 'penalty_balance', 'operator' => '='],
        'penalty_balance_lower_than' => ['column' => 'penalty_balance', 'operator' => '<'],
        'penalty_balance_greater_than' => ['column' => 'penalty_balance', 'operator' => '>'],
    ];

    private const UPDATE_REQUEST_ATTRIBUTES = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'active'
    ];

    private static function convert_request_to_entity(MemberRequest $request): Member
    {
        $builder = new MemberBuilder();

        $entity = $builder->set_id(null)->set_active(true)->set_address($request->address)->set_email(new Email($request->email))->set_membership_date(Date::now())->set_name($request->name)->set_password(Hash::make($request->password))->set_penalty_balance(0)->set_phone(new Phone($request->phone))->build();

        return $entity;
    }

    public static function add(MemberRequest $request)
    {
        $entity = MemberServices::convert_request_to_entity($request);

        $repository = new MemberRepository($entity);
        $repository->save();
    }

    private static function take_update_attributes(MemberUpdateRequest $request): array
    {
        $attributes = [];
        
        foreach (static::UPDATE_REQUEST_ATTRIBUTES as $attribute) {
            if ($request->$attribute != null){
                $attributes[$attribute] = $request->$attribute;
            }
        }

        return $attribute;
    }

    public static function delete(MemberDeleteRequest $request)
    {
        $entity = MemberRepository::search([
            ['email', '=', $request->email]
        ])[0];

        $repository = new MemberRepository($entity);

        $repository->delete();
    }

    public static function update(MemberUpdateRequest $request)
    {
        $entity = MemberRepository::search([
            ['target_email', '=', $request->email]
        ])[0];

        $repository = new MemberRepository($entity);

        $attributes = static::take_update_attributes($request);

        $repository->update($attributes);

        $repository->save();
    }

    public static function search(MemberSearchRequest $request)
    {
        $attributes = [];

        foreach (static::SEARCH_REQUEST_ATTRIBUTES as $attribute => $search_detail) {
            if ($request->$attribute == null) continue;
            $attributes[] = [$search_detail['column'], $search_detail['operator'], $request->$attribute];
        }

        $entities = MemberRepository::search($attributes);

        return $entities;
    }
}
