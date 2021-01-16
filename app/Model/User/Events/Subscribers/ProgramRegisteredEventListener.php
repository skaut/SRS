<?php

declare(strict_types=1);

namespace App\Model\User\Events\Subscribers;

use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Settings;
use App\Model\User\Events\ProgramRegisteredEvent;
use App\Services\IMailService;
use App\Services\ISettingsService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ProgramRegisteredEventListener implements MessageHandlerInterface
{
    private IMailService $mailService;

    private ISettingsService $settingsService;

    public function __construct(IMailService $mailService, ISettingsService $settingsService)
    {
        $this->mailService     = $mailService;
        $this->settingsService = $settingsService;
    }

    public function __invoke(ProgramRegisteredEvent $event): void
    {
        if (! $event->isAlternate() && $event->isNotifyUser()) {
            $this->mailService->sendMailFromTemplate(new ArrayCollection([$event->getUser()]), null, Template::PROGRAM_REGISTERED, [
                TemplateVariable::SEMINAR_NAME => $this->settingsService->getValue(Settings::SEMINAR_NAME),
                TemplateVariable::PROGRAM_NAME => $event->getProgram()->getBlock()->getName(),
            ]);
        }
    }
}
