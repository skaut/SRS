<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class QueryBus
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    /**
     * @return mixed
     */
    public function handle(object $query)
    {
        return $this->bus->dispatch($query)->last(HandledStamp::class)->getResult();
    }
}
