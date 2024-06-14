<?php

declare(strict_types=1);

namespace App\Model\Group\Events\Subscribers;

use App\Model\Group\Commands\UpdateGroupPrograms;
use App\Model\Group\Events\GroupUpdatedEvent;
use App\Services\CommandBus;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GroupUpdatedEventListener implements MessageHandlerInterface
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    public function __invoke(GroupUpdatedEvent $event): void
    {
        $this->commandBus->handle(new UpdateGroupPrograms($event->getGroup()));
    }
}
