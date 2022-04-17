<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

class PageDto
{
    /**
     * @param string[]     $allowedRoles
     * @param ContentDto[] $mainContents
     * @param ContentDto[] $sidebarContents
     */
    public function __construct(
        /**
         * Název stránky.
         */
        protected string $name,
        /**
         * Cesta stránky.
         */
        protected string $slug,
        /**
         * Role, které mají na stránku přístup.
         */
        protected array $allowedRoles,
        /**
         * Obsahy v hlavní části stránky.
         */
        protected array $mainContents,
        /**
         * Obsahy v postranní části stránky.
         */
        protected array $sidebarContents,
        /**
         * Má stránka sidebar?
         */
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
