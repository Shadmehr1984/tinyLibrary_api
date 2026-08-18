<?php

namespace App\Domain\Builders;

use App\Domain\Entities\Category;

class CategoryBuilder extends Builder
{
    protected int|null $id;
    protected string $name;
    protected string $description;

    public function __construct()
    {
        $this->set_attributes_number(3);
    }

    public function set_id(int|null $id)
    {
        $this->id = $this->set_attribute($id, 'id');
        return $this;
    }

    public function set_description(string $description)
    {
        $this->description = $this->set_attribute($description, 'description');
        return $this;
    }

    public function set_name(string $name)
    {
        $this->name = $this->set_attribute($name, 'name');
        return $this;
    }

    public function build()
    {
        if ($this->check_all_attributes_is_set()) {
            $category = new Category($this->id, $this->name, $this->description);

            $this->unset_attributes();

            return $category;
        }
    }
}
