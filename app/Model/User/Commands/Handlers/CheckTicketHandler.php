<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\User\Commands\CheckTicket;
use App\Model\User\Repositories\TicketCheckRepository;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\TicketCheck;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CheckTicketHandler implements MessageHandlerInterface
{
    public function __construct(private readonly TicketCheckRepository $ticketCheckRepository, private readonly UserRepository $userRepository)
    {
    }

    public function __invoke(CheckTicket $command): void
    {
        $user = $command->getUser();

        $this->ticketCheckRepository->save(new TicketCheck($user, $command->getSubevent()));

        $user->setAttended(true);
        $this->userRepository->save($user);
    }
}
