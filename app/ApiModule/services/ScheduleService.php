<?php

namespace App\ApiModule\Services;


use ApiModule\DTO\Schedule\RoomDetailDTO;
use App\ApiModule\DTO\Schedule\BlockDetailDTO;
use App\ApiModule\DTO\Schedule\CalendarConfigDTO;
use App\ApiModule\DTO\Schedule\ProgramDetailDTO;
use App\ApiModule\DTO\Schedule\ProgramSaveDTO;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\Program;
use App\Model\Program\ProgramRepository;
use App\Model\Program\Room;
use App\Model\Program\RoomRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Kdyby\Translation\Translator;
use Nette;

/**
 * ScheduleService
 *
 * @package App\ApiModule\Services
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ScheduleService extends Nette\Object
{
    /** @var User */
    private $user;

    /** @var int */
    private $basicBlockDuration;

    /** @var Translator */
    private $translator;

    /** @var UserRepository */
    private $userRepository;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var BlockRepository */
    private $blockRepository;

    /** @var RoomRepository */
    private $roomRepository;

    /** @var SettingsRepository */
    private $settingsRepository;


    /**
     * ScheduleService constructor.
     * @param Translator $translator
     * @param UserRepository $userRepository
     * @param ProgramRepository $programRepository
     * @param BlockRepository $blockRepository
     * @param RoomRepository $roomRepository
     */
    public function __construct(Translator $translator, UserRepository $userRepository,
                                ProgramRepository $programRepository, BlockRepository $blockRepository,
                                RoomRepository $roomRepository, SettingsRepository $settingsRepository)
    {
        $this->translator = $translator;
        $this->userRepository = $userRepository;
        $this->programRepository = $programRepository;
        $this->blockRepository = $blockRepository;
        $this->roomRepository = $roomRepository;
        $this->settingsRepository = $settingsRepository;

        $this->basicBlockDuration = $this->settingsRepository->getValue(Settings::BASIC_BLOCK_DURATION);
    }

    /**
     * @param $userId
     */
    public function setUser($userId) {
        $this->user = $this->userRepository->findById($userId);
    }

    /**
     * @return ProgramDetailDTO[]
     */
    public function getAllPrograms() {
        $programs = $this->programRepository->findAll();
        $programDetailDTOs = [];
        foreach ($programs as $program)
            $programDetailDTOs[] = $this->convertProgramToProgramDetailDTO($program);
        return $programDetailDTOs;
    }

    /**
     * @return ProgramDetailDTO[]
     */
    public function getUserAllowedPrograms() {
        $programs = $this->programRepository->findUserAllowed($this->user);
        $programDetailDTOs = [];
        foreach ($programs as $program)
            $programDetailDTOs[] = $this->convertProgramToProgramDetailDTO($program);
        return $programDetailDTOs;
    }

    /**
     * @return BlockDetailDTO[]
     */
    public function getAllBlocks() {
        $blocks = $this->blockRepository->findAll();
        $blockDetailDTOs = [];
        foreach ($blocks as $block)
            $blockDetailDTOs[] = $this->convertBlockToBlockDetailDTO($block);
        return $blockDetailDTOs;
    }

    /**
     * @return RoomDetailDTO[]
     */
    public function getAllRooms() {
        $rooms = $this->roomRepository->findAll();
        $roomDetailDTOs = [];
        foreach ($rooms as $room)
            $roomDetailDTOs[] = $this->convertRoomToRoomDetailDTO($room);
        return $roomDetailDTOs;
    }

    /**
     * @return CalendarConfigDTO
     */
    public function getCalendarConfig() {
        $calendarConfigDTO = new CalendarConfigDTO();

        $fromDate = $this->settingsRepository->getDateValue(Settings::SEMINAR_FROM_DATE);
        $toDate = $this->settingsRepository->getDateValue(Settings::SEMINAR_TO_DATE);

        $calendarConfigDTO->setSeminarFromWeekDay($fromDate->format('w'));
        $calendarConfigDTO->setSeminarDuration($toDate->diff($fromDate)->d);
        $calendarConfigDTO->setSeminarFromYear($fromDate->format('Y'));
        $calendarConfigDTO->setSeminarFromMonth($fromDate->format('n') - 1);
        $calendarConfigDTO->setSeminarFromDay($fromDate->format('j'));
        $calendarConfigDTO->setBasicBlockDuration($this->basicBlockDuration);
        $calendarConfigDTO->setAllowedModifySchedule(
            $this->settingsRepository->getValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE) &&
            $this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE)
        );

        return $calendarConfigDTO;
    }

    public function saveProgram(ProgramSaveDTO $programSaveDTO) {

    }

    public function removeProgram($programId) {

    }

    public function attendProgram($programId) {

    }

    public function unattendProgram($programId) {

    }

    private function convertProgramToProgramDetailDTO(Program $program) {
        $programDetailDTO = new ProgramDetailDTO();

        $programDetailDTO->setId($program->getId());
        $programDetailDTO->setBlockId($program->getBlock() ? $program->getBlock()->getId() : null);
        $programDetailDTO->setRoomId($program->getRoom() ? $program->getRoom()->getId() : null);
        $programDetailDTO->setName($program->getBlock() ? $program->getBlock()->getName() : $this->translator->translate('common.schedule.no_block'));
        $programDetailDTO->setStart($program->getStart());
        $programDetailDTO->setEnd($program->getEnd());
        $programDetailDTO->setDuration($program->getDuration());
        $programDetailDTO->setAttendeesCount($program->getAttendeesCount());
        $programDetailDTO->setUserAttends($this->user ? $program->isAttendee($this->user) : false);
        $programDetailDTO->setBlocksPrograms($this->programRepository->findBlockedProgramsIdsByProgram($program, $this->basicBlockDuration));

        return $programDetailDTO;
    }

    private function convertBlockToBlockDetailDTO(Block $block) {
        $blockDetailDTO = new BlockDetailDTO();

        $blockDetailDTO->setId($block->getId());
        $blockDetailDTO->setName($block->getName());
        $blockDetailDTO->setCategoryName($block->getCategory() ? $block->getCategory()->getName() : $this->translator->translate('common.schedule.no_category'));
        $blockDetailDTO->setLectorName($block->getLector() ? $block->getLector()->getDisplayName() : $this->translator->translate('common.schedule.no_lector'));
        $blockDetailDTO->setLectorAbout($block->getLector() ? $block->getLector()->getAbout() : '');
        $blockDetailDTO->setDuration($block->getDuration());
        $blockDetailDTO->setCapacity($block->getCapacity());
        $blockDetailDTO->setMandatory($block->isMandatory());
        $blockDetailDTO->setPerex($block->getPerex());
        $blockDetailDTO->setDescription($block->getDescription());
        $blockDetailDTO->setProgramsCount($block->getProgramsCount());
        $blockDetailDTO->setTools($block->getTools());

        return $blockDetailDTO;
    }

    private function convertRoomToRoomDetailDTO(Room $room) {
        $roomDetailDTO = new RoomDetailDTO();

        $roomDetailDTO->setId($room->getId());
        $roomDetailDTO->setName($room->getName());

        return $roomDetailDTO;
    }
}