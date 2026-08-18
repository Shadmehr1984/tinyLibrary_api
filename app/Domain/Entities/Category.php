<?php

namespace App\Domain\Entities;

class Category extends Entity
{
    public function __construct(
        protected int|null $id,
        protected string $name,
        protected string $description
    ) {
    }

    public function get()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description
        ];
    }
}
