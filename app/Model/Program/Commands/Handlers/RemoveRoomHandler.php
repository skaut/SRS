<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Commands\RemoveRoom;
use App\Model\Program\Commands\SaveProgram;
use App\Model\Program\Repositories\RoomRepository;
use App\Services\CommandBus;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RemoveRoomHandler implements MessageHandlerInterface
{
    public function __construct(
        private CommandBus $commandBus,
        private EntityManagerInterface $em,
        private RoomRepository $roomRepository
    ) {
    }

    public function __invoke(RemoveRoom $command): void
    {
        $this->em->wrapInTransaction(function (EntityManager $em) use ($command): void {
            $room = $command->getRoom();

            foreach ($room->getPrograms() as $program) {
                $program->setRoom(null);
                $this->commandBus->handle(new SaveProgram($program));
            }

            $this->roomRepository->remove($room);
        });
    }
}
