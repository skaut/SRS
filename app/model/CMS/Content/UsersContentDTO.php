<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\CMS\Page;
use App\Model\Page\PageException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

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
