<?php

declare(strict_types=1);

namespace App\AdminModule\Forms;

use App\Model\Acl\Events\RoleUpdatedEvent;
use App\Model\Acl\Permission;
use App\Model\Acl\Repositories\PermissionRepository;
use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Acl\SrsResource;
use App\Model\Cms\Repositories\PageRepository;
use App\Services\AclService;
use App\Services\EventBus;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\Controls\SelectBox;
use Nettrine\ORM\EntityManagerDecorator;
use Nextras\FormComponents\Controls\DateTimeControl;
use stdClass;
use Throwable;

use function array_key_exists;
use function in_array;

/**
 * Formulář pro úpravu role.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class EditRoleFormFactory
{
    use Nette\SmartObject;

    /**
     * Upravovaná role.
     */
    private ?Role $role = null;

    private BaseFormFactory $baseFormFactory;

    private EntityManagerDecorator $em;

    private AclService $aclService;

    private RoleRepository $roleRepository;

    private PageRepository $pageRepository;

    private PermissionRepository $permissionRepository;

    private EventBus $eventBus;

    public function __construct(
        BaseFormFactory $baseFormFactory,
        EntityManagerDecorator $em,
        AclService $aclService,
        RoleRepository $roleRepository,
        PageRepository $pageRepository,
        PermissionRepository $permissionRepository,
        EventBus $eventBus
    ) {
        $this->baseFormFactory      = $baseFormFactory;
        $this->em                   = $em;
        $this->aclService           = $aclService;
        $this->roleRepository       = $roleRepository;
        $this->pageRepository       = $pageRepository;
        $this->permissionRepository = $permissionRepository;
        $this->eventBus             = $eventBus;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function create(int $id): Form
    {
        $this->role = $this->roleRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addText('name', 'admin.acl.roles_name')
            ->addRule(Form::FILLED, 'admin.acl.roles_name_empty')
            ->addRule(Form::IS_NOT_IN, 'admin.acl.roles_name_exists', $this->roleRepository->findOthersNames($id))
            ->addRule(Form::NOT_EQUAL, 'admin.acl.roles_name_reserved', 'test');

        $form->addCheckbox('registerable', 'admin.acl.roles_registerable_form');

        $registerableFromDateTime = new DateTimeControl('admin.acl.roles_registerable_from');
        $registerableFromDateTime
            ->setHtmlAttribute('data-toggle', 'tooltip')
            ->setHtmlAttribute('data-placement', 'bottom')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.acl.roles_registerable_from_note'));
        $form->addComponent($registerableFromDateTime, 'registerableFrom');

        $registerableToDateTime = new DateTimeControl('admin.acl.roles_registerable_to');
        $registerableToDateTime
            ->setHtmlAttribute('data-toggle', 'tooltip')
            ->setHtmlAttribute('data-placement', 'bottom')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.acl.roles_registerable_to_note'));
        $form->addComponent($registerableToDateTime, 'registerableTo');

        $form->addText('capacity', 'admin.acl.roles_capacity')
            ->setHtmlAttribute('data-toggle', 'tooltip')
            ->setHtmlAttribute('data-placement', 'bottom')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.acl.roles_capacity_note'))
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.acl.roles_capacity_format')
            ->addRule(Form::MIN, 'admin.acl.roles_capacity_low', $this->role->countUsers());

        $form->addCheckbox('approvedAfterRegistration', 'admin.acl.roles_approved_after_registration');

        // $form->addCheckbox('syncedWithSkautIs', 'admin.acl.roles_synced_with_skaut_is');

        $form->addCheckbox('feeFromSubevents', 'admin.acl.roles_fee_from_subevents_checkbox')
            ->addCondition(Form::EQUAL, false)
            ->toggle('fee');

        $form->addText('fee', 'admin.acl.roles_fee')
            ->setOption('id', 'fee')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.acl.roles_fee_format');

        $form->addText('minimumAge', 'admin.acl.roles.minimum_age.label')
            ->setHtmlAttribute('data-toggle', 'tooltip')
            ->setHtmlAttribute('data-placement', 'bottom')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.acl.roles.minimum_age.note'))
            ->addRule(Form::INTEGER, 'admin.acl.roles.minimum_age.error_format')
            ->addRule(Form::MIN, 'admin.acl.roles.minimum_age.error_low', 0);

        $form->addMultiSelect('permissions', 'admin.acl.roles_permissions', $this->preparePermissionsOptions());

        $pagesOptions = $this->pageRepository->getPagesOptions();

        $allowedPages = $form->addMultiSelect('pages', 'admin.acl.roles_pages', $pagesOptions);

        $form->addSelect('redirectAfterLogin', 'admin.acl.roles_redirect_after_login', $pagesOptions)
            ->setPrompt('')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.acl.roles_redirect_after_login_note'))
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateRedirectAllowed'], 'admin.acl.roles_redirect_after_login_restricted', [$allowedPages]);

        $rolesOptions = $this->aclService->getRolesWithoutRoleOptions($this->role->getId());

        $incompatibleRolesSelect = $form->addMultiSelect('incompatibleRoles', 'admin.acl.roles_incompatible_roles', $rolesOptions);

        $requiredRolesSelect = $form->addMultiSelect('requiredRoles', 'admin.acl.roles_required_roles', $rolesOptions);

        $incompatibleRolesSelect
            ->addCondition(Form::FILLED)
            ->addRule(
                [$this, 'validateIncompatibleAndRequiredCollision'],
                'admin.acl.roles_incompatible_collision',
                [$incompatibleRolesSelect, $requiredRolesSelect]
            );

        $requiredRolesSelect
            ->addCondition(Form::FILLED)
            ->addRule(
                [$this, 'validateIncompatibleAndRequiredCollision'],
                'admin.acl.roles_required_collision',
                [$incompatibleRolesSelect, $requiredRolesSelect]
            );

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-warning');

        $redirectAfterLoginValue = $this->role->getRedirectAfterLogin();

        $form->setDefaults([
            'id' => $id,
            'name' => $this->role->getName(),
            'registerable' => $this->role->isRegisterable(),
            'registerableFrom' => $this->role->getRegisterableFrom(),
            'registerableTo' => $this->role->getRegisterableTo(),
            'capacity' => $this->role->getCapacity(),
            'approvedAfterRegistration' => $this->role->isApprovedAfterRegistration(),
            // 'syncedWithSkautIs' => $this->role->isSyncedWithSkautIS(),
            'feeFromSubevents' => $this->role->getFee() === null,
            'fee' => $this->role->getFee(),
            'minimumAge' => $this->role->getMinimumAge(),
            'permissions' => $this->permissionRepository->findPermissionsIds($this->role->getPermissions()),
            'pages' => $this->pageRepository->findPagesSlugs($this->role->getPages()),
            'redirectAfterLogin' => array_key_exists($redirectAfterLoginValue, $pagesOptions) ? $redirectAfterLoginValue : null,
            'incompatibleRoles' => $this->roleRepository->findRolesIds($this->role->getIncompatibleRoles()),
            'requiredRoles' => $this->roleRepository->findRolesIds($this->role->getRequiredRoles()),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values): void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        $this->em->transactional(function () use ($values): void {
            $capacity = $values->capacity !== '' ? $values->capacity : null;

            $this->role->setName($values->name);
            $this->role->setRegisterable($values->registerable);
            $this->role->setRegisterableFrom($values->registerableFrom);
            $this->role->setRegisterableTo($values->registerableTo);
            $this->role->setCapacity($capacity);
            $this->role->setApprovedAfterRegistration($values->approvedAfterRegistration);
            // $this->role->setSyncedWithSkautIS($values->syncedWithSkautIs);
            $this->role->setMinimumAge($values->minimumAge);
            $this->role->setPermissions($this->permissionRepository->findPermissionsByIds($values->permissions));
            $this->role->setPages($this->pageRepository->findPagesBySlugs($values->pages));
            $this->role->setRedirectAfterLogin($values->redirectAfterLogin);
            $this->role->setIncompatibleRoles($this->roleRepository->findRolesByIds($values->incompatibleRoles));
            $this->role->setRequiredRoles($this->roleRepository->findRolesByIds($values->requiredRoles));

            if ($values->feeFromSubevents) {
                $this->role->setFee(null);
            } else {
                $this->role->setFee($values->fee);
            }

            $this->aclService->saveRole($this->role);

            $this->eventBus->handle(new RoleUpdatedEvent($this->role));
        });
    }

    /**
     * Vrátí možná oprávnění jako možnosti pro select.
     *
     * @return string[]
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    private function preparePermissionsOptions(): array
    {
        $options = [];

        $this->preparePermissionOption($options, Permission::ACCESS, SrsResource::ADMIN);
        $this->preparePermissionOption($options, Permission::MANAGE, SrsResource::CMS);
        $this->preparePermissionOption($options, Permission::ACCESS, SrsResource::PROGRAM);
        $this->preparePermissionOption($options, Permission::MANAGE_OWN_PROGRAMS, SrsResource::PROGRAM);
        $this->preparePermissionOption($options, Permission::MANAGE_ALL_PROGRAMS, SrsResource::PROGRAM);
        $this->preparePermissionOption($options, Permission::MANAGE_SCHEDULE, SrsResource::PROGRAM);
        $this->preparePermissionOption($options, Permission::MANAGE_CATEGORIES, SrsResource::PROGRAM);
        $this->preparePermissionOption($options, Permission::MANAGE_ROOMS, SrsResource::PROGRAM);
        $this->preparePermissionOption($options, Permission::MANAGE, SrsResource::USERS);
        $this->preparePermissionOption($options, Permission::MANAGE, SrsResource::PAYMENTS);
        $this->preparePermissionOption($options, Permission::MANAGE, SrsResource::ACL);
        $this->preparePermissionOption($options, Permission::MANAGE, SrsResource::MAILING);
        $this->preparePermissionOption($options, Permission::MANAGE, SrsResource::CONFIGURATION);

        return $options;
    }

    /**
     * Připraví oprávnění jako možnost pro select.
     *
     * @param string[] $options
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    private function preparePermissionOption(?array &$options, string $permissionName, string $resourceName): void
    {
        $permission                    = $this->permissionRepository->findByPermissionAndResourceName($permissionName, $resourceName);
        $options[$permission->getId()] = 'common.permission_name.' . $permissionName . '.' . $resourceName;
    }

    /**
     * Ověří kolize mezi vyžadovanými a nekompatibilními rolemi.
     *
     * @param int[][] $args
     *
     * @throws ConnectionException
     */
    public function validateIncompatibleAndRequiredCollision(MultiSelectBox $field, array $args): bool
    {
        $incompatibleRoles = $this->roleRepository->findRolesByIds($args[0]);
        $requiredRoles     = $this->roleRepository->findRolesByIds($args[1]);

        $this->em->getConnection()->beginTransaction();

        $this->role->setIncompatibleRoles($incompatibleRoles);
        $this->role->setRequiredRoles($requiredRoles);

        $valid = true;

        foreach ($this->roleRepository->findAll() as $role) {
            foreach ($role->getRequiredRolesTransitive() as $requiredRole) {
                if ($role->getIncompatibleRoles()->contains($requiredRole)) {
                    $valid = false;
                    break;
                }
            }

            if (! $valid) {
                break;
            }
        }

        $this->em->getConnection()->rollBack();

        return $valid;
    }

    /**
     * Ověří, zda stránka, kam mají být uživatelé přesměrování po přihlášení, je pro ně viditelná.
     *
     * @param string[][] $args
     */
    public function validateRedirectAllowed(SelectBox $field, array $args): bool
    {
        return in_array($field->getValue(), $args[0]);
    }
}
