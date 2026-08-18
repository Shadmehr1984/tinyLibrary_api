<?php 

namespace App\Repositories;

use App\Domain\Builders\LibrarianBuilder;
use App\Domain\Entities\Librarian;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Phone;
use App\Models\Librarian as LibrarianModel;

class LibrarianRepository extends Repository{
    protected static $model_class = LibrarianModel::class;
    protected static $builder_class = LibrarianBuilder::class;
    protected static $attributes_name = [];
    protected static $attributes_special_type = [
        'email' => Email::class,
        'phone' => Phone::class
    ];

    public function __construct(Librarian $librarian)
    {
        parent::__construct($librarian);
    }
}

?>