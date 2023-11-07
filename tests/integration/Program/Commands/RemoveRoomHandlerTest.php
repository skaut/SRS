<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\Commands\RemoveRoom;
use App\Model\Program\Program;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Program\Repositories\RoomRepository;
use App\Model\Program\Room;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use CommandHandlerTest;
use DateTimeImmutable;
use Throwable;

final class RemoveRoomHandlerTest extends CommandHandlerTest
{
    private SubeventRepository $subeventRepository;

    private RoomRepository $roomRepository;

    private ProgramRepository $programRepository;

    private BlockRepository $blockRepository;

    /**
     * Odstranění místnosti.
     *
     * @throws Throwable
     */
    public function testRemoveRoom(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $room = new Room('room', null);
        $this->roomRepository->save($room);

        $block = new Block('block', 60, null, false, ProgramMandatoryType::AUTO_REGISTERED);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $program->setRoom($room);
        $this->programRepository->save($program);

        $this->assertContains($room, $this->roomRepository->findAll());
        $this->assertEquals($room, $program->getRoom());

        $this->commandBus->handle(new RemoveRoom($room));

        $this->assertNotContains($room, $this->roomRepository->findAll());
        $this->assertNull($program->getRoom());
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [Room::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/RemoveRoomHandlerTest.neon']);

        parent::_before();

        $this->subeventRepository = $this->tester->grabService(SubeventRepository::class);
        $this->roomRepository     = $this->tester->grabService(RoomRepository::class);
        $this->programRepository  = $this->tester->grabService(ProgramRepository::class);
        $this->blockRepository    = $this->tester->grabService(BlockRepository::class);
    }
}
