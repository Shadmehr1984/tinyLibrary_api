<?php

namespace App\Repositories;

use App\Domain\Builders\Builder;
use App\Domain\Entities\Entity;
use Error;
use Illuminate\Database\Eloquent\Model;

class Repository implements RepositoryInterface
{
    protected static $model_class = Model::class;
    protected static $builder_class = Builder::class;
    protected static $attributes_name = [];
    //[attribute_name => attribute type class]
    protected static $attributes_special_type = [];

    protected Model $model;
    protected array $attributes;

    public function __construct(
        protected Entity $entity,
    ) {
        static::set_attributes_name();
        $this->set_attributes();
        $this->make_model();
    }

    /**
     * save in database
     */
    public function save()
    {
        static::convert_type_class_2_db_format($this->attributes);
        $this->fill_model();
        $this->model->save();
        static::convert_db_format_2_type_class(attributes: $this->attributes);
    }

    public function delete()
    {
        $this->model->delete();
    }

    public function update(array $attributes)
    {
        $this->entity->set($attributes);
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    public function change(Entity $entity)
    {
        $this->entity = $entity;
        $this->set_attributes();
        $this->make_model();
    }

    // $attributes: array[array[column, operator, value]]
    public static function search(array $attributes)
    {
        static::set_attributes_name();

        $models = static::$model_class::select(['*'])->get();

        foreach ($attributes as $attribute) {
            $models = $models->where($attribute[0], $attribute[1], $attribute[2]);
        }

        $models->get(null);

        if (!$models) return null;

        $entities = [];

        foreach ($models as $model) {
            $entities[] = static::build_entity($model);
        }

        return $entities;
    }

    protected static function build_entity($model)
    {
        static::convert_db_format_2_type_class(model: $model);

        $builder = new static::$builder_class();

        $functions_name = [];

        foreach (static::$attributes_name as $attribute_name) {
            $functions_name[] = "set_" . $attribute_name;
        }

        $attributes_count = sizeof(static::$attributes_name);

        for ($i = 0; $i < $attributes_count; $i++) {
            $attribute_name = static::$attributes_name[$i];
            $function_name = $functions_name[$i];
            $builder = $builder->$function_name($model->$attribute_name);
        }

        return $builder->build();
    }

    protected static function convert_db_format_2_type_class(Model $model = null, array $attributes = null)
    {
        if ($model) {
            foreach (static::$attributes_special_type as $name => $type_class) {
                try {
                    if ($model->$name) {
                        $model->$name = new $type_class($model->$name);
                    }
                } catch (Error $error) {
                    $model->$name = $type_class::from($model->$name);
                }
            }
        } else if ($attributes) {
            foreach (static::$attributes_special_type as $name => $type_class) {
                try {
                    $attributes[$name] = new $type_class($attributes[$name]);
                } catch (Error $error) {
                    $attributes[$name] = $attributes[$name];
                }
            }
        } else {
            throw new \InvalidArgumentException();
        }
    }

    protected static function convert_type_class_2_db_format(array $attributes)
    {
        foreach (static::$attributes_special_type as $name => $type_class) {
            if (!$attributes[$name]) continue;
            try {
                $attributes[$name] = $attributes[$name]->__toString();
            } catch (Error $error) {
                dump($attributes[$name]);//!
                $attributes[$name] = $attributes[$name]->name;
            }
        }
        return $attributes;
    }

    protected function fill_model()
    {
        foreach ($this->attributes as $name => $value) {
            $this->model->$name = $value;
        }
    }

    protected function make_model()
    {
        $find_model = null;
        if ($this->attributes['id']) {
            $find_model = static::$model_class::find($this->attributes['id']);
            $this->model = $find_model;
        }
        if (!$find_model) {
            $this->model = new static::$model_class();
        }
    }

    protected function set_attributes()
    {
        $this->attributes = $this->entity->get();
    }

    protected static function set_attributes_name()
    {
        if (static::$attributes_name) return;
        foreach (static::$model_class::first()->getAttributes() as $key => $value) {
            if ($key == 'remember_token') continue; //remove special attribute
            static::$attributes_name[] = $key;
        }
    }
}
