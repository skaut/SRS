<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

/**
 * DTO obsahu se seznamem uživatelů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class UsersContentDTO extends ContentDTO
{
    /**
     * Role, jejichž uživatelé budou vypsáni.
     * @var int[]
     */
    protected $roles;


    /**
     * UsersContent constructor.
     * @param string $type
     * @param string $heading
     * @param array $roles
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
