<?php

declare(strict_types=1);

namespace App\Model\User\Events\Subscribers;

use App\Model\User\Commands\UpdateUsersPrograms;
use App\Model\User\Events\UserApplicationUpdatedEvent;
use App\Model\User\Events\UserUpdatedEvent;
use App\Services\CommandBus;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UserUpdatedEventListener implements MessageHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function __invoke(UserUpdatedEvent $event): void
    {
        $this->commandBus->handle(new UpdateUsersPrograms(new ArrayCollection([$event->getUser()])));
    }
}
