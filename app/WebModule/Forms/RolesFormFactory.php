<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Enums\ApplicationState;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Queries\SettingDateTimeValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\AclService;
use App\Services\ApplicationService;
use App\Services\QueryBus;
use App\Utils\Validators;
use DateTimeImmutable;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Localization\ITranslator;
use stdClass;
use Throwable;

/**
 * Formulář pro změnu rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class RolesFormFactory
{
    use Nette\SmartObject;

    /**
     * Přihlášený uživatel.
     */
    private ?User $user = null;

    private BaseFormFactory $baseFormFactory;

    private QueryBus $queryBus;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private ApplicationService $applicationService;

    private ITranslator $translator;

    private Validators $validators;

    private AclService $aclService;

    public function __construct(
        BaseFormFactory $baseFormFactory,
        QueryBus $queryBus,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        ApplicationService $applicationService,
        ITranslator $translator,
        Validators $validators,
        AclService $aclService
    ) {
        $this->baseFormFactory    = $baseFormFactory;
        $this->queryBus           = $queryBus;
        $this->userRepository     = $userRepository;
        $this->roleRepository     = $roleRepository;
        $this->applicationService = $applicationService;
        $this->translator         = $translator;
        $this->validators         = $validators;
        $this->aclService         = $aclService;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function create(int $id): Form
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $rolesSelect = $form->addMultiSelect('roles', 'web.profile.roles')->setItems(
            $this->aclService->getRolesOptionsWithCapacity(true, true, $this->user)
        )
            ->addRule(Form::FILLED, 'web.profile.roles_empty')
            ->addRule([$this, 'validateRolesCapacities'], 'web.profile.roles_capacity_occupied')
            ->addRule([$this, 'validateRolesRegisterable'], 'web.profile.roles_not_registerable')
            ->addRule([$this, 'validateRolesMinimumAge'], 'web.application_content.roles_require_minimum_age')
            ->setDisabled(! $this->applicationService->isAllowedEditRegistration($this->user));

        foreach ($this->roleRepository->findFilteredRoles(true, false, true, $this->user) as $role) {
            if (! $role->getIncompatibleRoles()->isEmpty()) {
                $rolesSelect->addRule(
                    [$this, 'validateRolesIncompatible'],
                    $this->translator->translate(
                        'web.profile.incompatible_roles_selected',
                        null,
                        ['role' => $role->getName(), 'incompatibleRoles' => $role->getIncompatibleRolesText()]
                    ),
                    [$role]
                );
            }

            if (! $role->getRequiredRolesTransitive()->isEmpty()) {
                $rolesSelect->addRule(
                    [$this, 'validateRolesRequired'],
                    $this->translator->translate(
                        'web.profile.required_roles_not_selected',
                        null,
                        ['role' => $role->getName(), 'requiredRoles' => $role->getRequiredRolesTransitiveText()]
                    ),
                    [$role]
                );
            }
        }

        $submitButton = $form->addSubmit('submit', 'web.profile.change_roles');

        $cancelRegistrationButton = $form->addSubmit('cancelRegistration', 'web.profile.cancel_registration')
            ->setHtmlAttribute('class', 'btn-danger');

        if ($this->applicationService->isAllowedEditRegistration($this->user)) {
            $submitButton
                ->setHtmlAttribute('data-toggle', 'confirmation')
                ->setHtmlAttribute('data-content', $form->getTranslator()->translate('web.profile.change_roles_confirm'));
            $cancelRegistrationButton
                ->setHtmlAttribute('data-toggle', 'confirmation')
                ->setHtmlAttribute('data-content', $form->getTranslator()->translate('web.profile.cancel_registration_confirm'));
        } else {
            $submitButton
                ->setDisabled()
                ->setHtmlAttribute('data-toggle', 'tooltip')
                ->setHtmlAttribute('data-placement', 'bottom')
                ->setHtmlAttribute('title', $form->getTranslator()->translate('web.profile.change_roles_disabled'));
            $cancelRegistrationButton
                ->setDisabled()
                ->setHtmlAttribute('data-toggle', 'tooltip')
                ->setHtmlAttribute('data-placement', 'bottom')
                ->setHtmlAttribute('title', $form->getTranslator()->translate('web.profile.cancel_registration_disabled'));
        }

        $ticketDownloadFrom = $this->queryBus->handle(new SettingDateTimeValueQuery(Settings::TICKETS_FROM));
        if ($ticketDownloadFrom !== null) {
            $downloadTicketButton = $form->addSubmit('downloadTicket', 'web.profile.download_ticket')
                ->setHtmlAttribute('class', 'btn-secondary');

            if (
                $this->user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED))
                || ! $this->user->hasPaidEveryApplication()
                || $ticketDownloadFrom > new DateTimeImmutable()
            ) {
                $downloadTicketButton
                    ->setDisabled()
                    ->setHtmlAttribute('data-toggle', 'tooltip')
                    ->setHtmlAttribute('data-placement', 'bottom')
                    ->setHtmlAttribute('title', $form->getTranslator()->translate('web.profile.download_ticket_disabled'));
            }
        }

        $form->setDefaults([
            'id' => $id,
            'roles' => $this->roleRepository->findRolesIds($this->user->getRoles()),
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
        if ($form->isSubmitted() === $form['submit']) {
            $selectedRoles = $this->roleRepository->findRolesByIds($values->roles);
            $this->applicationService->updateRoles($this->user, $selectedRoles, $this->user);
        } elseif ($form->isSubmitted() === $form['cancelRegistration']) {
            $this->applicationService->cancelRegistration($this->user, ApplicationState::CANCELED, $this->user);
        }
    }

    /**
     * Ověří kapacitu rolí.
     */
    public function validateRolesCapacities(MultiSelectBox $field): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesCapacities($selectedRoles, $this->user);
    }

    /**
     * Ověří kompatibilitu rolí.
     *
     * @param Role[] $args
     */
    public function validateRolesIncompatible(MultiSelectBox $field, array $args): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        $testRole      = $args[0];

        return $this->validators->validateRolesIncompatible($selectedRoles, $testRole);
    }

    /**
     * Ověří výběr vyžadovaných rolí.
     *
     * @param Role[] $args
     */
    public function validateRolesRequired(MultiSelectBox $field, array $args): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        $testRole      = $args[0];

        return $this->validators->validateRolesRequired($selectedRoles, $testRole);
    }

    /**
     * Ověří registrovatelnost rolí.
     */
    public function validateRolesRegisterable(MultiSelectBox $field): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesRegisterable($selectedRoles, $this->user);
    }

    /**
     * Ověří požadovaný minimální věk.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function validateRolesMinimumAge(MultiSelectBox $field): bool
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());

        return $this->validators->validateRolesMinimumAge($selectedRoles, $this->user);
    }
}
