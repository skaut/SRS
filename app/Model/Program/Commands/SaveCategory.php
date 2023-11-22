<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Category;

class SaveCategory
{
    public function __construct(private Category $category, private Category|null $categoryOld)
    {
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getCategoryOld(): Category|null
    {
        return $this->categoryOld;
    }
}
