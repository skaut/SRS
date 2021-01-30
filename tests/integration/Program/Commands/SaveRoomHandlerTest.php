<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Commands\SaveRoom;
use App\Model\Program\Repositories\RoomRepository;
use App\Model\Program\Room;
use CommandHandlerTest;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Throwable;

final class SaveRoomHandlerTest extends CommandHandlerTest
{
    private RoomRepository $roomRepository;

    /**
     * Uložení místnosti.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function testSaveRoom(): void
    {
        $room = new Room('room', 10);

        $this->assertNotContains($room, $this->roomRepository->findAll());

        $this->commandBus->handle(new SaveRoom($room));

        $this->assertContains($room, $this->roomRepository->findAll());
    }

    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots(): array
    {
        return [Room::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/SaveRoomHandlerTest.neon']);
        parent::_before();

        $this->roomRepository = $this->tester->grabService(RoomRepository::class);
    }
}
