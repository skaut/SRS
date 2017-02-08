<?php

namespace App\Model\CMS\Content;


use App\Model\ACL\RoleRepository;
use App\Model\CMS\Page;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 * @ORM\Table(name="users_content")
 */
class UsersContent extends Content implements IContent
{
    protected $type = Content::USERS;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role")
     * @var ArrayCollection
     */
    protected $roles;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * UserBoxContent constructor.
     */
    public function __construct(Page $page, $area)
    {
        parent::__construct($page, $area);
        $this->roles = new ArrayCollection();
    }

    /**
     * @param RoleRepository $roleRepository
     */
    public function injectRoleRepository(RoleRepository $roleRepository) {
        $this->roleRepository = $roleRepository;
    }

    /**
     * @return ArrayCollection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param ArrayCollection $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    public function addContentForm(Form $form)
    {
        parent::addContentForm($form);

        $formContainer = $form[$this->getContentFormName()];

        $rolesIds = array_map(function($o) { return $o->getId(); }, $this->roles->toArray());
        $formContainer->addMultiSelect('roles', 'admin.cms.pages_content_users_roles', $this->roleRepository->getRolesWithoutGuestsOptions())
            ->setDefaultValue($rolesIds);

        return $form;
    }

    public function contentFormSucceeded(Form $form, \stdClass $values)
    {
        parent::contentFormSucceeded($form, $values);
        $values = $values[$this->getContentFormName()];
        $this->roles = $this->roleRepository->findRolesByIds($values['roles']);
    }
}