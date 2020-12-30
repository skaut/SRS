<?php

declare(strict_types=1);

namespace App\Model\Acl\Events\Subscribers;

use App\Model\Acl\Events\RoleChangedEvent;
use App\Model\User\Commands\UpdateUsersPrograms;
use eGen\MessageBus\Bus\CommandBus;

class RoleChangedEventListener
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function __invoke(RoleChangedEvent $event) : void
    {
        $this->commandBus->handle(new UpdateUsersPrograms($event->getRole()->getUsers()));
    }
}