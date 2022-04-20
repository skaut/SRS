<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

/**
 * DTO obsahu se seznamem uživatelů
 */
class UsersContentDto extends ContentDto
{
    /**
     * @param int[] $roles Role, jejichž uživatelé budou vypsáni
     */
    public function __construct(string $type, string $heading, protected array $roles)
    {
        parent::__construct($type, $heading);
    }

    /**
     * @return int[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
