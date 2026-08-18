<?php 

namespace App\Repositories;

use App\Domain\Entities\Entity;

interface RepositoryInterface{
    public function save();

    public function delete();

    public function update(array $attributes);

    public function change(Entity $entity);

    public static function search(array $attributes);
}

?>