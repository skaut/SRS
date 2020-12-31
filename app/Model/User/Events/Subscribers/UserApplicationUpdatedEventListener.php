<?php

declare(strict_types=1);

namespace App\Model\User\Events\Subscribers;

use App\Model\User\Commands\UpdateUsersPrograms;
use App\Model\User\Events\UserApplicationUpdatedEvent;
use Doctrine\Common\Collections\ArrayCollection;
use eGen\MessageBus\Bus\CommandBus;

class UserApplicationUpdatedEventListener
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function __invoke(UserApplicationUpdatedEvent $event) : void
    {
        $this->commandBus->handle(new UpdateUsersPrograms(new ArrayCollection([$event->getUser()])));
    }
}
