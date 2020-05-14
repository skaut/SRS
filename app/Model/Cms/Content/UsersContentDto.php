<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

/**
 * DTO obsahu se seznamem uživatelů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class UsersContentDto extends ContentDto
{
    /**
     * Role, jejichž uživatelé budou vypsáni.
     *
     * @var int[]
     */
    protected array $roles;

    /**
     * @param int[] $roles
     */
    public function __construct(string $type, string $heading, array $roles)
    {
        parent::__construct($type, $heading);
        $this->roles = $roles;
    }

    /**
     * @return int[]
     */
    public function getRoles() : array
    {
        return $this->roles;
    }
}
