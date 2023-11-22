<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Category;

class RemoveCategory
{
    public function __construct(private Category $category)
    {
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
