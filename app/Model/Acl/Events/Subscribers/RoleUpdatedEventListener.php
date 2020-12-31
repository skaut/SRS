<?php

declare(strict_types=1);

namespace App\Model\Acl\Events\Subscribers;

use App\Model\Acl\Events\RoleUpdatedEvent;
use App\Model\User\Commands\UpdateUsersPrograms;
use eGen\MessageBus\Bus\CommandBus;

class RoleUpdatedEventListener
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function __invoke(RoleUpdatedEvent $event) : void
    {
        $this->commandBus->handle(new UpdateUsersPrograms($event->getRole()->getUsers())); // todo: není potřeba při každé změně role
    }
}
