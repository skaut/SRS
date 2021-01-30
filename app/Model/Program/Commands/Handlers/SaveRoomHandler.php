<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Commands\SaveRoom;
use App\Model\Program\Repositories\RoomRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SaveRoomHandler implements MessageHandlerInterface
{
    private RoomRepository $roomRepository;

    public function __construct(RoomRepository $roomRepository)
    {
        $this->roomRepository = $roomRepository;
    }

    public function __invoke(SaveRoom $command): void
    {
        $this->roomRepository->save($command->getRoom());
    }
}
