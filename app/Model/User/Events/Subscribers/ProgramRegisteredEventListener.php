<?php

declare(strict_types=1);

namespace App\Model\User\Events\Subscribers;

use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Events\ProgramRegisteredEvent;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ProgramRegisteredEventListener implements MessageHandlerInterface
{
    private CommandBus $commandBus;

    private QueryBus $queryBus;

    public function __construct(CommandBus $commandBus, QueryBus $queryBus)
    {
        $this->commandBus = $commandBus;
        $this->queryBus   = $queryBus;
    }

    public function __invoke(ProgramRegisteredEvent $event): void
    {
        if (! $event->isAlternate() && $event->isNotifyUser()) {
            $this->mailService->sendMailFromTemplate(new ArrayCollection([$event->getUser()]), null, Template::PROGRAM_REGISTERED, [
                TemplateVariable::SEMINAR_NAME => $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME)),
                TemplateVariable::PROGRAM_NAME => $event->getProgram()->getBlock()->getName(),
            ]);
        }
    }
}
