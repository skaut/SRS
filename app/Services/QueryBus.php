<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

use function assert;

final class QueryBus
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public function handle(object $query): mixed
    {
        $stamp = $this->bus->dispatch($query)->last(HandledStamp::class);
        assert($stamp instanceof HandledStamp);

        return $stamp->getResult();
    }
}
