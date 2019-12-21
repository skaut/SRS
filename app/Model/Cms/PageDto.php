<?php

declare(strict_types=1);

namespace App\Model\CMS;

use App\Model\CMS\Content\ContentDto;

class PageDto
{
    /**
     * Název stránky.
     *
     * @var string
     */
    protected $name;

    /**
     * Cesta stránky.
     *
     * @var string
     */
    protected $slug;

    /**
     * Role, které mají na stránku přístup.
     *
     * @var string[]
     */
    protected $allowedRoles;

    /**
     * Obsahy v hlavní části stránky.
     *
     * @var ContentDto[]
     */
    protected $mainContents;

    /**
     * Obsahy v postranní části stránky.
     *
     * @var ContentDto[]
     */
    protected $sidebarContents;

    /**
     * Má stránka sidebar?
     *
     * @var bool
     */
    protected $hasSidebar;

    /**
     * @param string[]     $allowedRoles
     * @param ContentDto[] $mainContents
     * @param ContentDto[] $sidebarContents
     */
    public function __construct(string $name, string $slug, array $allowedRoles, array $mainContents, array $sidebarContents, bool $hasSidebar)
    {
        $this->name            = $name;
        $this->slug            = $slug;
        $this->allowedRoles    = $allowedRoles;
        $this->mainContents    = $mainContents;
        $this->sidebarContents = $sidebarContents;
        $this->hasSidebar      = $hasSidebar;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getSlug() : string
    {
        return $this->slug;
    }

    /**
     * @return string[]
     */
    public function getAllowedRoles() : array
    {
        return $this->allowedRoles;
    }

    /**
     * @return ContentDto[]
     */
    public function getMainContents() : array
    {
        return $this->mainContents;
    }

    /**
     * @return ContentDto[]
     */
    public function getSidebarContents() : array
    {
        return $this->sidebarContents;
    }

    public function hasSidebar() : bool
    {
        return $this->hasSidebar;
    }

    /**
     * Je stránka viditelná pro uživatele v rolích?
     *
     * @param string[] $userRoles
     */
    public function isAllowedForRoles(array $userRoles) : bool
    {
        foreach ($userRoles as $userRole) {
            foreach ($this->allowedRoles as $allowedRole) {
                if ($userRole === $allowedRole) {
                    return true;
                }
            }
        }

        return false;
    }
}
