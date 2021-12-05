<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\User\Queries\TicketChecksByUserAndSubeventQuery;
use App\Model\User\Repositories\TicketCheckRepository;
use App\Model\User\TicketCheck;
use phpDocumentor\Reflection\Types\Collection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class TicketChecksByUserAndSubeventQueryHandler implements MessageHandlerInterface
{
    private TicketCheckRepository $ticketCheckRepository;

    public function __construct(TicketCheckRepository $ticketCheckRepository)
    {
        $this->ticketCheckRepository = $ticketCheckRepository;
    }

    /**
     * @return Collection<int, TicketCheck>
     */
    public function __invoke(TicketChecksByUserAndSubeventQuery $query): Collection
    {
        return $this->ticketCheckRepository->findByUserAndSubevent($query->getUser(), $query->getSubevent());
    }
}
