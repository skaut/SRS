<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Messenger\MessageBusInterface;

final class EventBus
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public function handle(object $event): void
    {
        $this->bus->dispatch($event);
    }
}
