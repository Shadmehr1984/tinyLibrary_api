<?php 

namespace App\Repositories;

use App\Domain\Builders\CategoryBuilder;
use App\Domain\Entities\Category;
use App\Models\Category as CategoryModel;
use Psy\Test\Fixtures\TabCompletion\StaticSample;

class CategoryRepository extends Repository{
    protected static $model_class = CategoryModel::class;
    protected static $builder_class = CategoryBuilder::class;
    protected static $attributes_name = [];
    protected static $attributes_special_type = [];

    public function __construct(Category $category)
    {
        parent::__construct($category);
    }
}

?>