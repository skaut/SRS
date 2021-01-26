<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Category;

class SaveCategory
{
    private Category $category;

    private ?Category $categoryOld;

    public function __construct(Category $category, ?Category $categoryOld)
    {
        $this->category    = $category;
        $this->categoryOld = $categoryOld;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getCategoryOld(): ?Category
    {
        return $this->categoryOld;
    }
}
