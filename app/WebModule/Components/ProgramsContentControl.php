<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Cms\Dto\ContentDto;
use App\Model\Enums\ProgramRegistrationType;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\IsAllowedRegisterProgramsQuery;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Queries\SettingDateTimeValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Services\QueryBus;
use Nette\Application\UI\Control;
use Throwable;

/**
 * Komponenta s výběrem programů.
 */
class ProgramsContentControl extends Control
{
    private QueryBus $queryBus;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    public function __construct(
        QueryBus $queryBus,
        UserRepository $userRepository,
        RoleRepository $roleRepository
    ) {
        $this->queryBus       = $queryBus;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function render(ContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/programs_content.latte');

        $template->heading = $content->getHeading();

        $template->backlink = $this->getPresenter()->getHttpRequest()->getUrl()->getPath();

        $template->registerProgramsAllowed       = $this->queryBus->handle(new IsAllowedRegisterProgramsQuery());
        $template->registerProgramsNotAllowed    = $this->queryBus->handle(new SettingStringValueQuery(Settings::REGISTER_PROGRAMS_TYPE)) === ProgramRegistrationType::NOT_ALLOWED;
        $template->registerProgramsAllowedFromTo = $this->queryBus->handle(new SettingStringValueQuery(Settings::REGISTER_PROGRAMS_TYPE)) === ProgramRegistrationType::ALLOWED_FROM_TO;
        $template->registerProgramsFrom          = $this->queryBus->handle(new SettingDateTimeValueQuery(Settings::REGISTER_PROGRAMS_FROM));
        $template->registerProgramsTo            = $this->queryBus->handle(new SettingDateTimeValueQuery(Settings::REGISTER_PROGRAMS_TO));

        $user                = $this->getPresenter()->user;
        $template->guestRole = $user->isInRole($this->roleRepository->findBySystemName(Role::GUEST)->getName());

        if ($user->isLoggedIn()) {
            $template->userWaitingForPayment = ! $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT))
                && $this->userRepository->findById($user->getId())->getWaitingForPaymentApplications()->count() > 0;
        }

        $template->render();
    }
}
