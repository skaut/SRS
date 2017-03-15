<?php

namespace App\Model\CMS\Content;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\CMS\Page;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 * @ORM\Table(name="capacities_content")
 */
class CapacitiesContent extends Content implements IContent
{
    protected $type = Content::CAPACITIES;

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
     * CapacitiesContent constructor.
     * @param Page $page
     * @param $area
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

        $formContainer->addMultiSelect('roles', 'admin.cms.pages_content_capacities_roles',
            $this->roleRepository->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED]))
            ->setDefaultValue($this->roleRepository->findRolesIds($this->roles));

        return $form;
    }

    public function contentFormSucceeded(Form $form, \stdClass $values)
    {
        parent::contentFormSucceeded($form, $values);
        $values = $values[$this->getContentFormName()];
        $this->roles = $this->roleRepository->findRolesByIds($values['roles']);
    }
}