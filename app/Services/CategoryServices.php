<?php 

namespace App\Services;

use App\Domain\Builders\CategoryBuilder;
use App\Domain\Entities\Category;
use App\Http\Requests\CategoryDeleteRequest;
use App\Http\Requests\CategoryRequest;
use App\Http\Requests\CategorySearchRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Repositories\CategoryRepository;

class CategoryServices{
    const SEARCH_REQUEST_ATTRIBUTES = [
        'name' => ['column' => 'name', 'operator' => '='],
        'description' => ['column' => 'description', 'operator' => '=']
    ];

    const UPDATE_REQUEST_ATTRIBUTES = [
        'name',
        'description'
    ];

    private static function convert_request_to_entity(CategoryRequest $request): Category{
        $builder = new CategoryBuilder();

        $entity = $builder->set_id(null)->
        set_description($request->description)->
        set_name($request->name)->
        build();

        return $entity;
    }

    private static function take_update_attributes(CategoryUpdateRequest $request): array
    {
        $attributes = [];
        
        foreach (static::UPDATE_REQUEST_ATTRIBUTES as $attribute) {
            if ($request->$attribute != null){
                $attributes[$attribute] = $request->$attribute;
            }
        }

        return $attribute;
    }

    public static function add(CategoryRequest $request){
        $entity = CategoryServices::convert_request_to_entity($request);

        $repository = new CategoryRepository($entity);
        $repository->save();
    }

    public static function delete(CategoryDeleteRequest $request){
        $entity = CategoryRepository::search([
            ['id', '=', $request->id]
        ])[0];

        $repository = new CategoryRepository($entity);

        $repository->delete();
    }

    public static function update(CategoryUpdateRequest $request)
    {
        $entity = CategoryRepository::search([
            ['id', '=', $request->target_id]
        ])[0];

        $repository = new CategoryRepository($entity);

        $attributes = static::take_update_attributes($request);

        $repository->update($attributes);

        $repository->save();
    }

    public static function search(CategorySearchRequest $request)
    {
        $attributes = [];

        foreach (static::SEARCH_REQUEST_ATTRIBUTES as $attribute => $search_detail) {
            if ($request->$attribute == null) continue;
            $attributes[] = [$search_detail['column'], $search_detail['operator'], $request->$attribute];
        }

        $entities = CategoryRepository::search($attributes);

        return $entities;
    }
}

?>