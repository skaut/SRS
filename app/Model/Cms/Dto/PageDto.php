<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

class PageDto
{
    /**
     * @param string       $name            Název stránky
     * @param string       $slug            Cesta stránky
     * @param string[]     $allowedRoles    Role, které mají na stránku přístup
     * @param ContentDto[] $mainContents    Obsahy v hlavní části stránky
     * @param ContentDto[] $sidebarContents Obsahy v postranní části stránky
     * @param bool         $hasSidebar      Má stránka sidebar?
     */
    public function __construct(
        protected string $name,
        protected string $slug,
        protected array $allowedRoles,
        protected array $mainContents,
        protected array $sidebarContents,
        protected bool $hasSidebar
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @return string[]
     */
    public function getAllowedRoles(): array
    {
        return $this->allowedRoles;
    }

    /**
     * @return ContentDto[]
     */
    public function getMainContents(): array
    {
        return $this->mainContents;
    }

    /**
     * @return ContentDto[]
     */
    public function getSidebarContents(): array
    {
        return $this->sidebarContents;
    }

    public function hasSidebar(): bool
    {
        return $this->hasSidebar;
    }

    /**
     * Je stránka viditelná pro uživatele v rolích?
     *
     * @param string[] $userRoles
     */
    public function isAllowedForRoles(array $userRoles): bool
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
