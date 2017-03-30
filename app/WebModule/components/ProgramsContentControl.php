<?php

namespace App\WebModule\Components;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
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


    /**
     * ProgramsContentControl constructor.
     * @param UserRepository $userRepository
     * @param RoleRepository $roleRepository
     * @param SettingsRepository $settingsRepository
     */
    public function __construct(UserRepository $userRepository, RoleRepository $roleRepository,
                                SettingsRepository $settingsRepository)
    {
        parent::__construct();

        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @param $content
     */
    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/programs_content.latte');

        $template->heading = $content->getHeading();

        $template->backlink = $this->getPresenter()->getHttpRequest()->getUrl()->getPath();

        $template->registerProgramsAllowed = $this->settingsRepository->getValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS) &&
            $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM) <= new \DateTime() &&
            $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO) >= new \DateTime();

        $user = $this->getPresenter()->user;
        $template->guestRole = $user->isInRole($this->roleRepository->findBySystemName(Role::GUEST)->getName());

        $template->userHasPermission = $user->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS);

        if ($user->isLoggedIn()) {
            $dbuser = $this->userRepository->findById($this->presenter->user->id);
            $template->userNotPaid = !$this->settingsRepository->getValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT) &&
                !$dbuser->hasPaid() && $dbuser->isPaying();
        }

        $template->render();
    }
}
