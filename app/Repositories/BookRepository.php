<?php 

namespace App\Repositories;

use App\Domain\Builders\BookBuilder;
use App\Domain\Entities\Book;
use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\ISBN;
use App\Models\Book as BookModel;

class BookRepository extends Repository{
    protected static $model_class = BookModel::class;
    protected static $builder_class = BookBuilder::class;
    protected static $attributes_name = [];
    protected static $attributes_special_type = [
        'isbn' => ISBN::class,
        'published' => Date::class,
        'deleted_at' => Date::class
    ];

    public function __construct(Book $book)
    {
        parent::__construct($book);
    }
}

?>