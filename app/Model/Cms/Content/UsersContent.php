<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

use App\Model\Acl\Role;
use App\Model\Acl\RoleRepository;
use App\Model\Cms\Page;
use App\Model\Cms\PageException;
use App\Services\AclService;
use App\Utils\Helpers;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use stdClass;

/**
 * Entita obsahu se seznamem uživatelů.
 *
 * @ORM\Entity
 * @ORM\Table(name="users_content")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class UsersContent extends Content implements IContent
{
    /** @var string */
    protected $type = Content::USERS;

    /**
     * Role, jejichž uživatelé budou vypsáni.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Acl\Role")
     *
     * @var Collection|Role[]
     */
    protected $roles;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var AclService */
    private $aclService;

    /**
     * @throws PageException
     */
    public function __construct(Page $page, string $area)
    {
        parent::__construct($page, $area);
        $this->roles = new ArrayCollection();
    }

    public function injectRoleRepository(RoleRepository $roleRepository) : void
    {
        $this->roleRepository = $roleRepository;
    }

    public function injectAclService(AclService $aclService) : void
    {
        $this->AclService = $aclService;
    }

    /**
     * @return Collection|Role[]
     */
    public function getRoles() : Collection
    {
        return $this->roles;
    }

    /**
     * @param Collection|Role[] $roles
     */
    public function setRoles(Collection $roles) : void
    {
        $this->roles->clear();
        foreach ($roles as $role) {
            $this->roles->add($role);
        }
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(Form $form) : Form
    {
        parent::addContentForm($form);

        $formName      = $this->getContentFormName();
        $formContainer = $form->$formName;

        $formContainer->addMultiSelect(
            'roles',
            'admin.cms.pages_content_users_roles',
            $this->aclService->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED])
        )
            ->setDefaultValue($this->roleRepository->findRolesIds($this->roles));

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     */
    public function contentFormSucceeded(Form $form, stdClass $values) : void
    {
        parent::contentFormSucceeded($form, $values);
        $formName    = $this->getContentFormName();
        $values      = $values->$formName;
        $this->roles = $this->roleRepository->findRolesByIds($values->roles);
    }

    public function convertToDto() : ContentDto
    {
        return new UsersContentDto($this->getComponentName(), $this->heading, Helpers::getIds($this->roles));
    }
}
