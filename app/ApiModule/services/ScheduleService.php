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
use Doctrine\Common\Collections\ArrayCollection;
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
    public function setUser($userId)
    {
        $this->user = $this->userRepository->findById($userId);
    }

    /**
     * @return ProgramDetailDTO[]
     */
    public function getProgramsAdmin()
    {
        $programs = $this->programRepository->findAll();
        $programAdminDetailDTOs = [];
        foreach ($programs as $program)
            $programAdminDetailDTOs[] = $this->convertProgramToProgramDetailDTO($program);
        return $programAdminDetailDTOs;
    }

    /**
     * @return ProgramDetailDTO[]
     */
    public function getProgramsWeb()
    {
        $programs = $this->programRepository->findUserAllowed($this->user);
        $programDetailDTOs = [];
        foreach ($programs as $program) {
            $programDetailDTO = $this->convertProgramToProgramDetailDTO($program);
            $programDetailDTO->setAttendeesCount($program->getAttendeesCount());
            $programDetailDTO->setUserAttends($program->isAttendee($this->user));
            $programDetailDTO->setBlocks($this->programRepository->findBlockedProgramsIdsByProgram($program));
            $programDetailDTO->setBlocked(false);
            $programDetailDTOs[] = $programDetailDTO;
        }

        foreach ($programDetailDTOs as $p1) {
            foreach ($programDetailDTOs as $p2) {
                if ($p1 != $p2 && $p1->isUserAttends() && in_array($p2->getId(), $p1->getBlocks()))
                    $p2->setBlocked(true);
            }
        }

        return $programDetailDTOs;
    }

    /**
     * @return BlockDetailDTO[]
     */
    public function getBlocks()
    {
        $blocks = $this->blockRepository->findAll();
        $blockDetailDTOs = [];
        foreach ($blocks as $block)
            $blockDetailDTOs[] = $this->convertBlockToBlockDetailDTO($block);
        return $blockDetailDTOs;
    }

    /**
     * @return RoomDetailDTO[]
     */
    public function getRooms()
    {
        $rooms = $this->roomRepository->findAll();
        $roomDetailDTOs = [];
        foreach ($rooms as $room)
            $roomDetailDTOs[] = $this->convertRoomToRoomDetailDTO($room);
        return $roomDetailDTOs;
    }

    /**
     * @return CalendarConfigDTO
     */
    public function getCalendarConfig()
    {
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

    /**
     * @param ProgramSaveDTO $programSaveDTO
     * @return ResponseDTO
     */
    public function saveProgram(ProgramSaveDTO $programSaveDTO)
    {
        if ($programSaveDTO->getId())
            $program = $this->programRepository->findById($programSaveDTO->getId());
        else
            $program = new Program();

        $responseDTO = new ResponseDTO();
        $responseDTO->setStatus('danger');

        $block = $this->blockRepository->findById($programSaveDTO->getBlockId());
        $room = $programSaveDTO->getRoomId() ? $this->roomRepository->findById($programSaveDTO->getRoomId()) : null;
        $start = $programSaveDTO->getStart();
        $end = clone $start;
        $end->add(new \DateInterval('PT' . $block->getDuration() . 'M'));

        if (!$this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE))
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_user_not_allowed_manage'));
        elseif (!$this->settingsRepository->getValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE))
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_not_allowed_modfify'));
        elseif ($room && $this->roomRepository->hasOverlappingProgram($room, $program, $start, $end))
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_room_occupied', null, ['name' => $room->getName()]));
        elseif (false)
            $responseDTO->setMessage(); //TODO
        else {
            $program->setBlock($block);
            $program->setRoom($room);
            $program->setStart($start);

            $this->programRepository->save($program);

            $responseDTO = new ResponseDTO();
            $responseDTO->setProgram($this->convertProgramToProgramDetailDTO($program));
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_saved'));
            $responseDTO->setStatus('success');
        }

        return $responseDTO;
    }

    /**
     * @param $programId
     * @return ResponseDTO
     */
    public function removeProgram($programId)
    {
        $program = $this->programRepository->findById($programId);

        $responseDTO = new ResponseDTO();
        $responseDTO->setStatus('danger');

        if (!$this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE))
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_user_not_allowed_manage'));
        elseif (!$this->settingsRepository->getValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE))
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_not_allowed_modfify'));
        elseif (!$program)
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_not_found'));
        else {
            $programDetailDTO = new ProgramDetailDTO();
            $programDetailDTO->setId($program->getId());

            $this->programRepository->remove($program);

            $responseDTO->setProgram($programDetailDTO);
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_deleted'));
            $responseDTO->setStatus('success');
        }

        return $responseDTO;
    }

    public function attendProgram($programId)
    {
        $program = $this->programRepository->find($programId);

        $responseDTO = new ResponseDTO();
        $responseDTO->setStatus('danger');

        if (!$this->user->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS))
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_user_not_allowed_register_programs'));
        elseif (!($this->settingsRepository->getValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS) &&
            $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM) <= new \DateTime() &&
            $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO) >= new \DateTime())
        )
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_register_programs_not_allowed'));
        elseif (!$this->settingsRepository->getValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT) &&
            !$this->user->hasPaid() && $this->user->isPaying())
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_register_programs_before_payment_not_allowed'));
        elseif (!$program)
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_not_found'));
        elseif ($this->user->getPrograms()->contains($program))
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_already_registered'));
        elseif ($program->getCapacity() !== null && $program->getCapacity() <= $program->getAttendeesCount())
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_no_vacancies'));
        elseif (!(new ArrayCollection($this->programRepository->findUserAllowed($this->user)))->contains($program))
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_category_not_allowed'));
        elseif (count(
            array_intersect($this->programRepository->findBlockedProgramsIdsByProgram($program),
                $this->programRepository->findProgramsIds($this->user->getPrograms()))
        ))
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_blocked'));
        else {
            $this->user->addProgram($program);
            $this->userRepository->save($this->user);

            $responseDTO->setMessage($this->translator->translate('common.api.program_registered'));
            $responseDTO->setStatus('success');

            $programDetailDTO = $this->convertProgramToProgramDetailDTO($program);
            $programDetailDTO->setAttendeesCount($program->getAttendeesCount());

            $responseDTO->setProgram($programDetailDTO);
        }

        return $responseDTO;
    }

    public function unattendProgram($programId)
    {
        $program = $this->programRepository->find($programId);

        $responseDTO = new ResponseDTO();
        $responseDTO->setStatus('danger');

        if (!($this->settingsRepository->getValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS) &&
            $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM) <= new \DateTime() &&
            $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO) >= new \DateTime())
        )
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_register_programs_not_allowed'));
        elseif (!$program)
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_not_found'));
        elseif (!$this->user->getPrograms()->contains($program))
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_not_registered'));
        else {
            $this->user->removeProgram($program);
            $this->userRepository->save($this->user);

            $responseDTO->setMessage($this->translator->translate('common.api.program_unregistered'));
            $responseDTO->setStatus('success');

            $programDetailDTO = $this->convertProgramToProgramDetailDTO($program);
            $programDetailDTO->setAttendeesCount($program->getAttendeesCount());

            $responseDTO->setProgram($programDetailDTO);
        }

        return $responseDTO;
    }

    /**
     * @param Program $program
     * @return ProgramDetailDTO
     */
    private function convertProgramToProgramDetailDTO(Program $program)
    {
        $programDetailDTO = new ProgramDetailDTO();

        $programDetailDTO->setId($program->getId());
        $programDetailDTO->setTitle($program->getBlock()->getName());
        $programDetailDTO->setStart($program->getStart()->format(DATE_ISO8601));
        $programDetailDTO->setEnd($program->getEnd()->format(DATE_ISO8601));
        $programDetailDTO->setBlockId($program->getBlock()->getId());
        $programDetailDTO->setRoomId($program->getRoom() ? $program->getRoom()->getId() : null);

        return $programDetailDTO;
    }

    /**
     * @param Block $block
     * @return BlockDetailDTO
     */
    private function convertBlockToBlockDetailDTO(Block $block)
    {
        $blockDetailDTO = new BlockDetailDTO();

        $blockDetailDTO->setId($block->getId());
        $blockDetailDTO->setName($block->getName());
        $blockDetailDTO->setCategory($block->getCategory() ? $block->getCategory()->getName() : '');
        $blockDetailDTO->setLector($block->getLector() ? $block->getLector()->getDisplayName() : '');
        $blockDetailDTO->setAboutLector($block->getLector() ? $block->getLector()->getAbout() : '');
        $blockDetailDTO->setDurationHours(floor($block->getDuration() / 60));
        $blockDetailDTO->setDurationMinutes($block->getDuration() % 60);
        $blockDetailDTO->setCapacity($block->getCapacity());
        $blockDetailDTO->setMandatory($block->getMandatory() > 0);
        $blockDetailDTO->setAutoRegister($block->getMandatory() == 2);
        $blockDetailDTO->setPerex($block->getPerex());
        $blockDetailDTO->setDescription($block->getDescription());
        $blockDetailDTO->setProgramsCount($block->getProgramsCount());
        $blockDetailDTO->setUserAttends($block->isAttendee($this->user));
        $blockDetailDTO->setUserAllowed($block->isAllowed($this->user));

        return $blockDetailDTO;
    }

    /**
     * @param Room $room
     * @return RoomDetailDTO
     */
    private function convertRoomToRoomDetailDTO(Room $room)
    {
        $roomDetailDTO = new RoomDetailDTO();

        $roomDetailDTO->setId($room->getId());
        $roomDetailDTO->setName($room->getName());

        return $roomDetailDTO;
    }
}