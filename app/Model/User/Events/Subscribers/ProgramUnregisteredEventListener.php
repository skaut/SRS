<?php

declare(strict_types=1);

namespace App\Model\User\Events\Subscribers;

use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Settings;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Events\ProgramUnregisteredEvent;
use App\Model\User\Repositories\UserRepository;
use App\Services\CommandBus;
use App\Services\MailService;
use App\Services\SettingsService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ProgramUnregisteredEventListener implements MessageHandlerInterface
{
    private CommandBus $commandBus;

    private UserRepository $userRepository;

    private MailService $mailService;

    private SettingsService $settingsService;

    public function __construct(
        CommandBus $commandBus,
        UserRepository $userRepository,
        MailService $mailService,
        SettingsService $settingsService
    ) {
        $this->commandBus      = $commandBus;
        $this->userRepository  = $userRepository;
        $this->mailService     = $mailService;
        $this->settingsService = $settingsService;
    }

    public function __invoke(ProgramUnregisteredEvent $event): void
    {
        if (! $event->isAlternate()) {
            $alternate = $this->userRepository->findProgramFirstAlternate($event->getProgram());

            if ($alternate !== null) {
                $this->commandBus->handle(new RegisterProgram($alternate, $event->getProgram()));
            }

            if ($event->isNotifyUser()) {
                $this->mailService->sendMailFromTemplate(new ArrayCollection([$event->getUser()]), null, Template::PROGRAM_UNREGISTERED, [
                    TemplateVariable::SEMINAR_NAME => $this->settingsService->getValue(Settings::SEMINAR_NAME),
                    TemplateVariable::PROGRAM_NAME => $event->getProgram()->getBlock()->getName(),
                ]);
            }
        }
    }
}
