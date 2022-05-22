<?php

declare(strict_types=1);

namespace App\Model\Program\Events;

use App\Model\Acl\Role;
use App\Model\Program\Category;
use Doctrine\Common\Collections\Collection;

class CategoryUpdatedEvent
{
    /**
     * @param Collection<int, Role> $registerableRolesOld
     */
    public function __construct(private Category $category, private Collection $registerableRolesOld)
    {
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getRegisterableRolesOld(): Collection
    {
        return $this->registerableRolesOld;
    }
}
