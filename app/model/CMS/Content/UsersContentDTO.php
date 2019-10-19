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
 * Entita obsahu se seznamem uživatelů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="users_content")
 */
class UsersContent extends Content implements IContent
{
    /** @var string */
    protected $type = Content::USERS;

    /**
     * Role, jejichž uživatelé budou vypsáni.
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role")
     * @var Collection|Role[]
     */
    protected $roles;

    /**
     * @throws PageException
     */
    public function __construct(Page $page, string $area)
    {
        parent::__construct($page, $area);
        $this->roles = new ArrayCollection();
    }

    /**
     * @return Collection|Role[]
     */
    public function getRoles() : Collection
    {
        return $this->roles;
    }
}
