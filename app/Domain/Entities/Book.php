<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\ISBN;

class Book extends Entity
{
    public function __construct(
        protected int|null $id,
        protected string $title,
        protected string $author,
        protected ISBN $isbn,
        protected Date $published,
        protected string $publisher,
        protected int $category_id,
        protected int $total_copies,
        protected int $available_copies,
        protected string $description,
        protected string $location,
        protected Date|null $deleted_at
    ) {}

    public function get(){
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published' => $this->published,
            'publisher' => $this->publisher,
            'category_id' => $this->category_id,
            'total_copies' => $this->total_copies,
            'available_copies' => $this->available_copies,
            'description' => $this->description,
            'location' => $this->location,
            'deleted_at' => $this->deleted_at == null ? null : $this->deleted_at
        ];
    }
}
