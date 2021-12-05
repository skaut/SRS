<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\User\Commands\SaveTicketCheck;
use App\Model\User\Repositories\TicketCheckRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SaveTicketCheckHandler implements MessageHandlerInterface
{
    private TicketCheckRepository $ticketCheckRepository;

    public function __construct(
        TicketCheckRepository $ticketCheckRepository
    ) {
        $this->ticketCheckRepository = $ticketCheckRepository;
    }

    public function __invoke(SaveTicketCheck $command): void
    {
        $this->ticketCheckRepository->save($command->getTicketCheck());
    }
}
