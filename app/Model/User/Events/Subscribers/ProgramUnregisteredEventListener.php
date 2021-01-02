<?php

declare(strict_types=1);

namespace App\Model\User\Events\Subscribers;

use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\Settings\Settings;
use App\Model\User\Events\ProgramUnregisteredEvent;
use App\Services\MailService;
use App\Services\SettingsService;
use Doctrine\Common\Collections\ArrayCollection;
use eGen\MessageBus\Bus\QueryBus;

class ProgramUnregisteredEventListener
{
    private QueryBus $queryBus;

    private ProgramApplicationRepository $programApplicationRepository;

    private MailService $mailService;

    private SettingsService $settingsService;

    public function __construct(
        QueryBus $queryBus,
        ProgramApplicationRepository $programApplicationRepository,
        MailService $mailService,
        SettingsService $settingsService
    ) {
        $this->queryBus                      = $queryBus;
        $this->programApplicationRepository  = $programApplicationRepository;
        $this->mailService                   = $mailService;
        $this->settingsService               = $settingsService;
    }

    public function __invoke(ProgramUnregisteredEvent $event) : void
    {
        if ($event->isNotifyUser()) {
            $this->mailService->sendMailFromTemplate(new ArrayCollection([$event->getUser()]), null, Template::PROGRAM_UNREGISTERED, [
                TemplateVariable::SEMINAR_NAME => $this->settingsService->getValue(Settings::SEMINAR_NAME),
                TemplateVariable::PROGRAM_NAME => $event->getProgram()->getBlock()->getName(),
            ]);
        }

        // todo prihlaseni nahradniku
    }
}
