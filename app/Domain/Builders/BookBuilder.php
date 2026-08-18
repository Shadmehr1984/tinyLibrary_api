<?php

namespace App\Domain\Builders;

use App\Domain\Entities\Book;
use App\Domain\ValueObjects\ISBN;
use App\Domain\ValueObjects\Date;

class BookBuilder extends Builder
{
    protected int|null $id;
    protected string $title;
    protected string $author;
    protected ISBN $isbn;
    protected Date $published;
    protected string $publisher;
    protected int $category_id;
    protected int $total_copies;
    protected int $available_copies;
    protected string $description;
    protected string $location;
    protected Date|null $deleted_at;

    public function __construct()
    {
        $this->set_attributes_number(12);
    }

    public function set_id(int|null $id)
    {
        $this->id = $this->set_attribute($id, 'id');
        return $this;
    }

    public function set_title(string $title)
    {
        $this->title = $this->set_attribute($title, 'title');
        return $this;
    }

    public function set_author(string $author)
    {
        $this->author = $this->set_attribute($author, 'author');
        return $this;
    }

    public function set_isbn(ISBN $isbn)
    {
        $this->isbn = $this->set_attribute($isbn, 'isbn');
        return $this;
    }

    public function set_published(Date $published)
    {
        $this->published = $this->set_attribute($published, 'published');
        return $this;
    }

    public function set_publisher(string $publisher)
    {
        $this->publisher = $this->set_attribute($publisher, 'publisher');
        return $this;
    }

    public function set_category_id(int $category_id)
    {
        $this->category_id = $this->set_attribute($category_id, 'category');
        return $this;
    }

    public function set_total_copies(int $total_copies)
    {
        $this->total_copies = $this->set_attribute($total_copies, 'total_copies');
        return $this;
    }

    public function set_available_copies(int $available_copies)
    {
        $this->available_copies = $this->set_attribute($available_copies, 'available_copies');
        return $this;
    }

    public function set_description(string $description)
    {
        $this->description = $this->set_attribute($description, 'description');
        return $this;
    }

    public function set_location(string $location)
    {
        $this->location = $this->set_attribute($location, 'location');
        return $this;
    }

    public function set_deleted_at(Date|null $deleted_at)
    {
        $this->deleted_at = $this->set_attribute($deleted_at, 'deleted_at');
        return $this;
    }

    public function build()
    {
        if ($this->check_all_attributes_is_set()) {
            $book = new Book(
                $this->id,
                $this->title,
                $this->author,
                $this->isbn,
                $this->published,
                $this->publisher,
                $this->category_id,
                $this->total_copies,
                $this->available_copies,
                $this->description,
                $this->location,
                $this->deleted_at
            );

            $this->unset_attributes();

            return $book;
        }
    }
}
