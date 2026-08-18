<?php

declare(strict_types = 1);

namespace App\Domain\ValueObjects;

class ISBN
{
    const ISBN_RIGHT_SIZE = 13;

    private string $isbn;

    public function __construct(string $isbn)
    {
        $this->set($isbn);
    }

    public static function validate_isbn(string $isbn)
    {
        if (strlen($isbn) != static::ISBN_RIGHT_SIZE) {
            throw new \InvalidArgumentException('isbn must have 13 characters');
        }

        $isbn_characters = str_split($isbn);

        //validate numeric characters
        foreach ($isbn_characters as $index) {
            if (!is_numeric($isbn_characters[$index])) {
                throw new \InvalidArgumentException("character {$index}th must be numeric");
            }
        }
    }

    public function get()
    {
        return $this->isbn;
    }

    public function set(string $isbn)
    {
        static::validate_isbn($isbn);
        $this->isbn = $isbn;
    }

    public function __toString()
    {
        return $this->isbn;
    }
}
