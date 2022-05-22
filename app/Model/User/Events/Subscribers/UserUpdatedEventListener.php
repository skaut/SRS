<?php

declare(strict_types=1);

namespace App\Model\User\Events\Subscribers;

use App\Model\User\Commands\UpdateUserPrograms;
use App\Model\User\Events\UserUpdatedEvent;
use App\Services\CommandBus;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UserUpdatedEventListener implements MessageHandlerInterface
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    public function __invoke(UserUpdatedEvent $event): void
    {
        $this->commandBus->handle(new UpdateUserPrograms($event->getUser()));
    }
}
