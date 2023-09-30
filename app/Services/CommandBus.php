<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Messenger\MessageBusInterface;

final class CommandBus
{
    public function __construct(private readonly MessageBusInterface $bus)
    {
    }

    public function handle(object $command): void
    {
        $this->bus->dispatch($command);
    }
}
