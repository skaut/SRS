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
    private TicketCheckRepository $ticketCheckRepository;

    private UserRepository $userRepository;

    public function __construct(TicketCheckRepository $ticketCheckRepository, UserRepository $userRepository)
    {
        $this->ticketCheckRepository = $ticketCheckRepository;
        $this->userRepository        = $userRepository;
    }

    public function __invoke(CheckTicket $command): void
    {
        $user = $command->getUser();

        $this->ticketCheckRepository->save(new TicketCheck($user, $command->getSubevent()));

        $user->setAttended(true);
        $this->userRepository->save($user);
    }
}
