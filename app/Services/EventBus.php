<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Messenger\MessageBusInterface;

final class EventBus
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    public function handle(object $event): void
    {
        $this->bus->dispatch($event);
    }
}
