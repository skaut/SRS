<?php

declare(strict_types=1);

namespace App\Model\CMS;

use App\Model\CMS\Content\ContentDTO;

class PageDTO
{
    /**
     * Název stránky.
     * @var string
     */
    protected $name;

    /**
     * Role, které mají na stránku přístup.
     * @var int[]
     */
    protected $allowedRoles;

    /**
     * Obsahy v hlavní části stránky.
     * @var ContentDTO[]
     */
    protected $mainContents;

    /**
     * Obsahy v postranní části stránky.
     * @var ContentDTO[]
     */
    protected $sidebarContents;


    /**
     * PageDTO constructor.
     * @param string $name
     * @param int[] $allowedRoles
     * @param ContentDTO[] $mainContents
     * @param ContentDTO[] $sidebarContents
     */
    public function __construct(string $name, array $allowedRoles, array $mainContents, array $sidebarContents)
    {
        $this->name = $name;
        $this->allowedRoles = $allowedRoles;
        $this->mainContents = $mainContents;
        $this->sidebarContents = $sidebarContents;
    }
}