<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Role;
use App\Model\Cms\Dto\ContentDto;
use App\Model\Enums\ProgramRegistrationType;
use App\Model\Settings\Queries\IsAllowedRegisterProgramsQuery;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Queries\SettingDateTimeValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\AclService;
use App\Services\QueryBus;
use App\WebModule\Presenters\WebBasePresenter;
use Throwable;

use function assert;

/**
 * Komponenta obsahu s výběrem programů.
 */
class ProgramsContentControl extends BaseContentControl
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly AclService $aclService,
    ) {
    }

    /** @throws Throwable */
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

        $presenter = $this->getPresenter();
        assert($presenter instanceof WebBasePresenter);

        $template->guestRole = $presenter->getUser()->isInRole($this->aclService->findRoleNameBySystemName(Role::GUEST));

        if ($presenter->getUser()->isLoggedIn()) {
            $template->userWaitingForPayment = ! $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT))
                && $presenter->getDbUser()->getWaitingForPaymentApplications()->count() > 0;
        }

        $template->render();
    }
}
