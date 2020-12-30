<?php

declare(strict_types=1);

namespace App\Model\Mailing\Events\Subscribers;

use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Settings;
use App\Model\User\Events\ProgramRegisteredEvent;
use App\Services\MailService;
use App\Services\SettingsService;
use Doctrine\Common\Collections\ArrayCollection;

class ProgramRegisteredEventMailListener
{
    private MailService $mailService;

    private SettingsService $settingsService;

    public function __construct(MailService $mailService, SettingsService $settingsService)
    {
        $this->mailService     = $mailService;
        $this->settingsService = $settingsService;
    }

    public function __invoke(ProgramRegisteredEvent $event) : void
    {
        if ($event->isNotifyUser()) {
            $this->mailService->sendMailFromTemplate(new ArrayCollection([$event->getUser()]), null, Template::PROGRAM_REGISTERED, [
                TemplateVariable::SEMINAR_NAME => $this->settingsService->getValue(Settings::SEMINAR_NAME),
                TemplateVariable::PROGRAM_NAME => $event->getProgram()->getBlock()->getName(),
            ]);
        }
    }
}