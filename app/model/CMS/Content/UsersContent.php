<?php
declare(strict_types=1);

namespace App\Model\CMS\Content;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\CMS\Page;
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
    protected $type = Content::USERS;

    /**
     * Role, jejichž uživatelé budou vypsáni.
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role")
     * @var Collection
     */
    protected $roles;

    /**
     * @var RoleRepository
     */
    private $roleRepository;


    /**
     * UsersContent constructor.
     * @param Page $page
     * @param $area
     * @throws \App\Model\Page\PageException
     */
    public function __construct(Page $page, $area)
    {
        parent::__construct($page, $area);
        $this->roles = new ArrayCollection();
    }

    /**
     * @param RoleRepository $roleRepository
     */
    public function injectRoleRepository(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    /**
     * @return Collection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param Collection $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     * @param Form $form
     * @return Form
     */
    public function addContentForm(Form $form)
    {
        parent::addContentForm($form);

        $formContainer = $form[$this->getContentFormName()];

        $formContainer->addMultiSelect('roles', 'admin.cms.pages_content_users_roles',
            $this->roleRepository->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED]))
            ->setDefaultValue($this->roleRepository->findRolesIds($this->roles));

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     * @param Form $form
     * @param array $values
     */
    public function contentFormSucceeded(Form $form, array $values)
    {
        parent::contentFormSucceeded($form, $values);
        $values = $values[$this->getContentFormName()];
        $this->roles = $this->roleRepository->findRolesByIds($values['roles']);
    }
}
