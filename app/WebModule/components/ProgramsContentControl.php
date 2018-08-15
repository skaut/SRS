<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\RegisterProgramsType;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use App\Services\ProgramService;
use Nette\Application\UI\Control;

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

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var ProgramService */
    private $programService;


    public function __construct(
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        SettingsRepository $settingsRepository,
        ProgramService $programService
    ) {
        parent::__construct();

        $this->userRepository     = $userRepository;
        $this->roleRepository     = $roleRepository;
        $this->settingsRepository = $settingsRepository;
        $this->programService     = $programService;
    }

    /**
     * @param $content
     * @throws SettingsException
     * @throws \Throwable
     */
    public function render($content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/programs_content.latte');

        $template->heading = $content->getHeading();

        $template->backlink = $this->getPresenter()->getHttpRequest()->getUrl()->getPath();

        $template->registerProgramsAllowed       = $this->programService->isAllowedRegisterPrograms();
        $template->registerProgramsNotAllowed    = $this->settingsRepository->getValue(Settings::REGISTER_PROGRAMS_TYPE) === RegisterProgramsType::NOT_ALLOWED;
        $template->registerProgramsAllowedFromTo = $this->settingsRepository->getValue(Settings::REGISTER_PROGRAMS_TYPE) === RegisterProgramsType::ALLOWED_FROM_TO;
        $template->registerProgramsFrom          = $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM);
        $template->registerProgramsTo            = $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO);

        $user                = $this->getPresenter()->user;
        $template->guestRole = $user->isInRole($this->roleRepository->findBySystemName(Role::GUEST)->getName());

        if ($user->isLoggedIn()) {
            $template->userHasPermission     = $user->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS);
            $template->userWaitingForPayment = ! $this->settingsRepository->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT)
                && $this->userRepository->findById($user->getId())->getWaitingForPaymentApplications()->count() > 0;
        }

        $template->render();
    }
}
