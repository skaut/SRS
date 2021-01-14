<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Category;

class SaveCategory
{
    private Category $category;

    public function __construct(Category $category)
    {
        $this->category = $category;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
