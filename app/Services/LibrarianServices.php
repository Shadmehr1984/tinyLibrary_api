<?php

namespace App\Services;

use App\Domain\Builders\LibrarianBuilder;
use App\Domain\Entities\Librarian;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Phone;
use App\Http\Requests\LibrarianDeleteRequest;
use App\Http\Requests\LibrarianRequest;
use App\Http\Requests\LibrarianSearchRequest;
use App\Http\Requests\LibrarianUpdateRequest;
use App\Repositories\LibrarianRepository;
use Illuminate\Support\Facades\Hash;

class LibrarianServices
{
    private const SEARCH_REQUEST_ATTRIBUTES = [
        'name' => ['column' => 'name', 'operator' => '='],
        'email' => ['column' => 'email', 'operator' => '='],
        'phone' => ['column' => 'phone', 'operator' => '='],
        'address' => ['column' => 'address', 'operator' => '='],
    ];

    private const UPDATE_REQUEST_ATTRIBUTES = [
        'name',
        'email',
        'password',
        'phone',
        'address'
    ];

    private static function convert_request_to_entity(LibrarianRequest $request): Librarian
    {
        $builder = new LibrarianBuilder();

        $entity = $builder->set_id(null)->set_address($request->address)->set_email(new Email($request->email))->set_name($request->name)->set_password(Hash::make($request->password))->set_phone(new Phone($request->phone))->build();

        return $entity;
    }

    private static function take_update_attributes(LibrarianUpdateRequest $request): array
    {
        $attributes = [];
        
        foreach (static::UPDATE_REQUEST_ATTRIBUTES as $attribute) {
            if ($request->$attribute != null){
                $attributes[$attribute] = $request->$attribute;
            }
        }

        if (isset($attribute['password'])){
            $attribute['password'] = Hash::make($attribute['password']);
        }

        return $attribute;
    }

    public static function add(LibrarianRequest $request)
    {
        $entity = LibrarianServices::convert_request_to_entity($request);

        $repository = new LibrarianRepository($entity);
        $repository->save();
    }

    public static function delete(LibrarianDeleteRequest $request)
    {
        $entity = LibrarianRepository::search([
            ['email', '=', $request->email]
        ])[0];

        $repository = new LibrarianRepository($entity);

        $repository->delete();
    }

    public static function update(LibrarianUpdateRequest $request)
    {
        $entity = LibrarianRepository::search([
            ['target_email', '=', $request->email]
        ])[0];

        $repository = new LibrarianRepository($entity);

        $attributes = static::take_update_attributes($request);

        $repository->update($attributes);

        $repository->save();
    }

    public static function search(LibrarianSearchRequest $request)
    {
        $attributes = [];

        foreach (static::SEARCH_REQUEST_ATTRIBUTES as $attribute => $search_detail) {
            if ($request->$attribute == null) continue;
            $attributes[] = [$search_detail['column'], $search_detail['operator'], $request->$attribute];
        }

        $entities = LibrarianRepository::search($attributes);

        return $entities;
    }
}
