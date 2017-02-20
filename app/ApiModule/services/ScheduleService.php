<?php

namespace App\ApiModule\Services;


use ApiModule\DTO\Schedule\RoomDetailDTO;
use App\ApiModule\DTO\Schedule\BlockDetailDTO;
use App\ApiModule\DTO\Schedule\CalendarConfigDTO;
use App\ApiModule\DTO\Schedule\ProgramAddDTO;
use App\ApiModule\DTO\Schedule\ProgramDetailDTO;
use App\ApiModule\DTO\Schedule\ProgramSaveDTO;
use App\ApiModule\DTO\Schedule\Response;
use App\ApiModule\DTO\Schedule\ResponseDTO;
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
    public function getProgramsAdmin() {
        $programs = $this->programRepository->findAll();
        $programAdminDetailDTOs = [];
        foreach ($programs as $program)
            $programAdminDetailDTOs[] = $this->convertProgramToProgramDetailDTO($program);
        return $programAdminDetailDTOs;
    }

    /**
     * @return ProgramDetailDTO[]
     */
    public function getProgramsWeb() {
//        $programs = $this->programRepository->findUserAllowed($this->user);
//        $programDetailDTOs = [];
//        foreach ($programs as $program)
//            $programDetailDTOs[] = $this->convertProgramToProgramDetailDTO($program);
//        return $programDetailDTOs;
    }

    /**
     * @return BlockDetailDTO[]
     */
    public function getBlocks() {
        $blocks = $this->blockRepository->findAll();
        $blockDetailDTOs = [];
        foreach ($blocks as $block)
            $blockDetailDTOs[] = $this->convertBlockToBlockDetailDTO($block);
        return $blockDetailDTOs;
    }

    /**
     * @return RoomDetailDTO[]
     */
    public function getRooms() {
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

        $calendarConfigDTO->setSeminarFromDate($fromDate->format('Y-m-d'));
        $calendarConfigDTO->setSeminarDuration($toDate->diff($fromDate)->d + 1);
        $calendarConfigDTO->setAllowedModifySchedule(
            $this->settingsRepository->getValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE) &&
            $this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE)
        );

        return $calendarConfigDTO;
    }

    public function saveProgram(ProgramSaveDTO $programSaveDTO) {
        if ($programSaveDTO->getId())
            $program = $this->programRepository->findById($programSaveDTO->getId());
        else
            $program = new Program();

        $program->setBlock($this->blockRepository->findById($programSaveDTO->getBlockId()));
        $program->setRoom($programSaveDTO->getRoomId() ? $this->roomRepository->findById($programSaveDTO->getRoomId()) : null);
        $program->setStart($programSaveDTO->getStart());

        $this->programRepository->save($program);

        $responseDTO = new ResponseDTO();
        $responseDTO->setEventId($program->getId());
        $responseDTO->setMessage($this->translator->translate('admin.program.schedule_saved'));
        $responseDTO->setStatus('success');

        return $responseDTO;
    }

    public function removeProgram($programId) {
        $program = $this->programRepository->findById($programId);
        $this->programRepository->remove($program);

        $responseDTO = new ResponseDTO();
        $responseDTO->setEventId($program->getId());
        $responseDTO->setMessage($this->translator->translate('admin.program.schedule_deleted'));
        $responseDTO->setStatus('success');

        return $responseDTO;
    }

    public function attendProgram($programId) {

    }

    public function unattendProgram($programId) {

    }

    /**
     * @param Program $program
     * @return ProgramDetailDTO
     */
    private function convertProgramToProgramDetailDTO(Program $program) {
        $programDetailDTO = new ProgramDetailDTO();

        $programDetailDTO->setId($program->getId());
        $programDetailDTO->setTitle($program->getBlock()->getName());
        $programDetailDTO->setStart($program->getStart()->format(DATE_ISO8601));
        $programDetailDTO->setEnd($program->getEnd()->format(DATE_ISO8601));
        $programDetailDTO->setBlockId($program->getBlock()->getId());
        $programDetailDTO->setRoomId($program->getRoom() ? $program->getRoom()->getId() : null);

        return $programDetailDTO;
    }

    private function convertBlockToBlockDetailDTO(Block $block) {
        $blockDetailDTO = new BlockDetailDTO();

        $blockDetailDTO->setId($block->getId());
        $blockDetailDTO->setName($block->getName());
        $blockDetailDTO->setCategory($block->getCategory() ? $block->getCategory()->getName() : '');//$this->translator->translate('common.schedule.no_category'));
        $blockDetailDTO->setLector($block->getLector() ? $block->getLector()->getDisplayName() : '');//$this->translator->translate('common.schedule.no_lector'));
        $blockDetailDTO->setAboutLector($block->getLector() ? $block->getLector()->getAbout() : '');
        $blockDetailDTO->setDurationHours(floor($block->getDuration()/60));
        $blockDetailDTO->setDurationMinutes($block->getDuration()%60);
        $blockDetailDTO->setCapacity($block->getCapacity());
        $blockDetailDTO->setMandatory($block->isMandatory());
        $blockDetailDTO->setPerex($block->getPerex());
        $blockDetailDTO->setDescription($block->getDescription());
        $blockDetailDTO->setProgramsCount($block->getProgramsCount());

        return $blockDetailDTO;
    }

    private function convertRoomToRoomDetailDTO(Room $room) {
        $roomDetailDTO = new RoomDetailDTO();

        $roomDetailDTO->setId($room->getId());
        $roomDetailDTO->setName($room->getName());

        return $roomDetailDTO;
    }
}