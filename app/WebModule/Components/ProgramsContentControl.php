<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Permission;
use App\Model\Acl\Role;
use App\Model\Acl\RoleRepository;
use App\Model\Acl\SrsResource;
use App\Model\Cms\Content\ContentDto;
use App\Model\Enums\ProgramRegistrationType;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\User\UserRepository;
use App\Services\ProgramService;
use App\Services\SettingsService;
use Nette\Application\UI\Control;
use Throwable;

/**
 * Komponenta s výběrem programů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramsContentControl extends Control
{
    /** @var UserRepository */
    private $userRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var SettingsService */
    private $settingsService;

    /** @var ProgramService */
    private $programService;

    public function __construct(
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        SettingsService $settingsService,
        ProgramService $programService
    ) {
        parent::__construct();

        $this->userRepository  = $userRepository;
        $this->roleRepository  = $roleRepository;
        $this->settingsService = $settingsService;
        $this->programService  = $programService;
    }

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    public function render(ContentDto $content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/programs_content.latte');

        $template->heading = $content->getHeading();

        $template->backlink = $this->getPresenter()->getHttpRequest()->getUrl()->getPath();

        $template->registerProgramsAllowed       = $this->programService->isAllowedRegisterPrograms();
        $template->registerProgramsNotAllowed    = $this->settingsService->getValue(Settings::REGISTER_PROGRAMS_TYPE) === ProgramRegistrationType::NOT_ALLOWED;
        $template->registerProgramsAllowedFromTo = $this->settingsService->getValue(Settings::REGISTER_PROGRAMS_TYPE) === ProgramRegistrationType::ALLOWED_FROM_TO;
        $template->registerProgramsFrom          = $this->settingsService->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM);
        $template->registerProgramsTo            = $this->settingsService->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO);

        $user                = $this->getPresenter()->user;
        $template->guestRole = $user->isInRole($this->roleRepository->findBySystemName(Role::GUEST)->getName());

        if ($user->isLoggedIn()) {
            $template->userHasPermission     = $user->isAllowed(SrsResource::PROGRAM, Permission::CHOOSE_PROGRAMS);
            $template->userWaitingForPayment = ! $this->settingsService->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT)
                && $this->userRepository->findById($user->getId())->getWaitingForPaymentApplications()->count() > 0;
        }

        $template->render();
    }
}
