<?php

declare(strict_types=1);

namespace App\Model\Acl\Events;

use App\Model\Acl\Role;
use App\Model\Program\Category;
use Doctrine\Common\Collections\Collection;

class CategoryUpdatedEvent
{
    /** @var Category */
    private Category $category;

    /** @var Collection<Role> */
    private Collection $originalRegisterableRoles;

    /**
     * @param Collection<Role> $originalRegisterableRoles
     */
    public function __construct(Category $category, Collection $originalRegisterableRoles)
    {
        $this->category                  = $category;
        $this->originalRegisterableRoles = $originalRegisterableRoles;
    }

    public function getCategory() : Category
    {
        return $this->category;
    }

    /**
     * @return Collection<Role>
     */
    public function getOriginalRegisterableRoles() : Collection
    {
        return $this->originalRegisterableRoles;
    }
}