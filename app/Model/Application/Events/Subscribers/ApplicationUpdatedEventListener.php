<?php

declare(strict_types=1);

namespace App\Model\Application\Events\Subscribers;

use App\Model\Application\Events\ApplicationUpdatedEvent;
use App\Model\User\Commands\UpdateUserPrograms;
use App\Services\CommandBus;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ApplicationUpdatedEventListener implements MessageHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function __invoke(ApplicationUpdatedEvent $event): void
    {
        $this->commandBus->handle(new UpdateUserPrograms($event->getUser()));
    }
}
