<?php

declare(strict_types=1);

namespace App\ApiModule\Services;

use ApiModule\DTO\Schedule\RoomDetailDTO;
use App\ApiModule\DTO\Schedule\BlockDetailDTO;
use App\ApiModule\DTO\Schedule\CalendarConfigDTO;
use App\ApiModule\DTO\Schedule\ProgramDetailDTO;
use App\ApiModule\DTO\Schedule\ProgramSaveDTO;
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
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ProgramService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Translation\Translator;
use Nette;
use const DATE_ISO8601;
use function array_intersect;
use function count;
use function floor;
use function in_array;

/**
 * Služba pro zpracování požadavků z API pro správu harmonogramu a zapisování programů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ScheduleService
{
    use Nette\SmartObject;

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

    /** @var ProgramService */
    private $programService;


    public function __construct(
        Translator $translator,
        UserRepository $userRepository,
        ProgramRepository $programRepository,
        BlockRepository $blockRepository,
        RoomRepository $roomRepository,
        SettingsRepository $settingsRepository,
        ProgramService $programService
    ) {
        $this->translator         = $translator;
        $this->userRepository     = $userRepository;
        $this->programRepository  = $programRepository;
        $this->blockRepository    = $blockRepository;
        $this->roomRepository     = $roomRepository;
        $this->settingsRepository = $settingsRepository;
        $this->programService     = $programService;
    }

    public function setUser($userId) : void
    {
        $this->user = $this->userRepository->findById($userId);
    }

    /**
     * Vrací podrobnosti o všech programech pro použití v administraci harmonogramu.
     * @return ProgramDetailDTO[]
     * @throws \Exception
     */
    public function getProgramsAdmin()
    {
        $programs               = $this->programRepository->findAll();
        $programAdminDetailDTOs = [];
        foreach ($programs as $program) {
            $programAdminDetailDTOs[] = $this->convertProgramToProgramDetailDTO($program);
        }
        return $programAdminDetailDTOs;
    }

    /**
     * Vrací podrobnosti o programech, ke kterým má uživatel přístup, pro použití v kalendáři pro výběr programů.
     * @return ProgramDetailDTO[]
     * @throws SettingsException
     * @throws \Throwable
     */
    public function getProgramsWeb()
    {
        $programs          = $this->programService->getUserAllowedPrograms($this->user);
        $programDetailDTOs = [];
        foreach ($programs as $program) {
            $programDetailDTO = $this->convertProgramToProgramDetailDTO($program);
            $programDetailDTO->setAttendeesCount($program->getAttendeesCount());
            $programDetailDTO->setUserAttends($program->isAttendee($this->user));
            $programDetailDTO->setBlocks($this->programRepository->findBlockedProgramsIdsByProgram($program));
            $programDetailDTO->setBlocked(false);
            $programDetailDTO->setPaid($programPaid = $this->settingsRepository->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT)
                || $this->user->hasPaidSubevent($program->getBlock()->getSubevent()));
            $programDetailDTOs[]                    = $programDetailDTO;
        }

        foreach ($programDetailDTOs as $p1) {
            foreach ($programDetailDTOs as $p2) {
                if ($p1 === $p2 || ! $p1->isUserAttends() || ! in_array($p2->getId(), $p1->getBlocks())) {
                    continue;
                }

                $p2->setBlocked(true);
            }
        }

        return $programDetailDTOs;
    }

    /**
     * Vrací podrobnosti o programových blocích.
     * @return BlockDetailDTO[]
     */
    public function getBlocks()
    {
        $blocks          = $this->blockRepository->findAll();
        $blockDetailDTOs = [];
        foreach ($blocks as $block) {
            $blockDetailDTOs[] = $this->convertBlockToBlockDetailDTO($block);
        }
        return $blockDetailDTOs;
    }

    /**
     * Vrací podrobnosti o místnostech.
     * @return RoomDetailDTO[]
     */
    public function getRooms()
    {
        $rooms          = $this->roomRepository->findAll();
        $roomDetailDTOs = [];
        foreach ($rooms as $room) {
            $roomDetailDTOs[] = $this->convertRoomToRoomDetailDTO($room);
        }
        return $roomDetailDTOs;
    }

    /**
     * Vrací nastavení pro FullCalendar.
     * @throws SettingsException
     * @throws \Throwable
     */
    public function getCalendarConfig() : CalendarConfigDTO
    {
        $calendarConfigDTO = new CalendarConfigDTO();

        $fromDate = $this->settingsRepository->getDateValue(Settings::SEMINAR_FROM_DATE);
        $toDate   = $this->settingsRepository->getDateValue(Settings::SEMINAR_TO_DATE);

        $calendarConfigDTO->setSeminarFromDate($fromDate->format('Y-m-d'));
        $calendarConfigDTO->setSeminarDuration($toDate->diff($fromDate)->d + 1);
        $calendarConfigDTO->setAllowedModifySchedule(
            $this->settingsRepository->getBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE) &&
            $this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE)
        );

        return $calendarConfigDTO;
    }

    /**
     * Uloží nebo vytvoří program.
     * @throws SettingsException
     * @throws \Throwable
     */
    public function saveProgram(ProgramSaveDTO $programSaveDTO) : ResponseDTO
    {
        if ($programSaveDTO->getId()) {
            $program = $this->programRepository->findById($programSaveDTO->getId());
        } else {
            $program = new Program();
        }

        $responseDTO = new ResponseDTO();
        $responseDTO->setStatus('danger');

        $block = $this->blockRepository->findById($programSaveDTO->getBlockId());
        $room  = $programSaveDTO->getRoomId() ? $this->roomRepository->findById($programSaveDTO->getRoomId()) : null;
        $start = $programSaveDTO->getStart();
        $end   = clone $start;
        $end->add(new \DateInterval('PT' . $block->getDuration() . 'M'));

        if (! $this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_user_not_allowed_manage'));
        } elseif (! $this->settingsRepository->getBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_not_allowed_modfify'));
        } elseif ($room && $this->roomRepository->hasOverlappingProgram($room, $program, $start, $end)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_room_occupied', null, ['name' => $room->getName()]));
        } elseif ($block->getMandatory() === 2 && $this->programRepository->hasOverlappingProgram($program, $start, $end)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_auto_register_not_allowed'));
        } elseif ($this->programRepository->hasOverlappingAutoRegisterProgram($program, $start, $end)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_auto_register_not_allowed'));
        } else {
            $program->setBlock($block);
            $program->setRoom($room);
            $program->setStart($start);
            $this->programRepository->save($program);

            if ($block->getMandatory() === 2) {
                foreach ($this->userRepository->findProgramAllowed($program) as $attendee) {
                    $program->addAttendee($attendee);
                }
                $this->programRepository->save($program);
            }

            $responseDTO = new ResponseDTO();
            $responseDTO->setProgram($this->convertProgramToProgramDetailDTO($program));
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_saved'));
            $responseDTO->setStatus('success');
        }

        return $responseDTO;
    }

    /**
     * Smaže program.
     * @param $programId
     * @throws SettingsException
     * @throws \Throwable
     */
    public function removeProgram($programId) : ResponseDTO
    {
        $program = $this->programRepository->findById($programId);

        $responseDTO = new ResponseDTO();
        $responseDTO->setStatus('danger');

        if (! $this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_user_not_allowed_manage'));
        } elseif (! $this->settingsRepository->getBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_not_allowed_modfify'));
        } elseif (! $program) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_not_found'));
        } else {
            $programDetailDTO = new ProgramDetailDTO();
            $programDetailDTO->setId($program->getId());

            $this->programRepository->remove($program);

            $responseDTO->setProgram($programDetailDTO);
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_deleted'));
            $responseDTO->setStatus('success');
        }

        return $responseDTO;
    }

    /**
     * Přihlásí program uživateli.
     * @param $programId
     * @throws SettingsException
     * @throws \Throwable
     */
    public function attendProgram($programId) : ResponseDTO
    {
        $program = $this->programRepository->findById($programId);

        $responseDTO = new ResponseDTO();
        $responseDTO->setStatus('danger');

        if (! $this->user->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_user_not_allowed_register_programs'));
        } elseif (! $this->programService->isAllowedRegisterPrograms()) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_register_programs_not_allowed'));
        } elseif (! $this->settingsRepository->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT) &&
            ! $this->user->hasPaidSubevent($program->getBlock()->getSubevent())
        ) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_register_programs_before_payment_not_allowed'));
        } elseif (! $program) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_not_found'));
        } elseif ($this->user->getPrograms()->contains($program)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_already_registered'));
        } elseif ($program->getCapacity() !== null && $program->getCapacity() <= $program->getAttendeesCount()) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_no_vacancies'));
        } elseif (! ($this->programService->getUserAllowedPrograms($this->user))->contains($program)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_category_not_allowed'));
        } elseif (count(
            array_intersect(
                $this->programRepository->findBlockedProgramsIdsByProgram($program),
                $this->programRepository->findProgramsIds($this->user->getPrograms())
            )
        )) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_blocked'));
        } else {
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

    /**
     * Odhlásí program uživateli.
     * @param $programId
     * @throws SettingsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Throwable
     */
    public function unattendProgram($programId) : ResponseDTO
    {
        $program = $this->programRepository->findById($programId);

        $responseDTO = new ResponseDTO();
        $responseDTO->setStatus('danger');

        if (! $this->programService->isAllowedRegisterPrograms()) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_register_programs_not_allowed'));
        } elseif (! $program) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_not_found'));
        } elseif (! $this->user->getPrograms()->contains($program)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_not_registered'));
        } else {
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
     * Převede Program na ProgramDetailDTO.
     * @throws \Exception
     */
    private function convertProgramToProgramDetailDTO(Program $program) : ProgramDetailDTO
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
     * Převede Block na BlockDetailDTO.
     */
    private function convertBlockToBlockDetailDTO(Block $block) : BlockDetailDTO
    {
        $blockDetailDTO = new BlockDetailDTO();

        $blockDetailDTO->setId($block->getId());
        $blockDetailDTO->setName($block->getName());
        $blockDetailDTO->setCategory($block->getCategory() ? $block->getCategory()->getName() : '');
        $blockDetailDTO->setLector($block->getLector() ? $block->getLector()->getLectorName() : '');
        $blockDetailDTO->setAboutLector($block->getLector() ? $block->getLector()->getAbout() : '');
        $blockDetailDTO->setLectorPhoto($block->getLector() ? $block->getLector()->getPhoto() : null);
        $blockDetailDTO->setDurationHours((int) floor($block->getDuration() / 60));
        $blockDetailDTO->setDurationMinutes($block->getDuration() % 60);
        $blockDetailDTO->setCapacity($block->getCapacity());
        $blockDetailDTO->setMandatory($block->getMandatory() > 0);
        $blockDetailDTO->setAutoRegister($block->getMandatory() === 2);
        $blockDetailDTO->setPerex($block->getPerex());
        $blockDetailDTO->setDescription($block->getDescription());
        $blockDetailDTO->setProgramsCount($block->getProgramsCount());
        $blockDetailDTO->setUserAttends($block->isAttendee($this->user));
        $blockDetailDTO->setUserAllowed($block->isAllowed($this->user));

        return $blockDetailDTO;
    }

    /**
     * Převede Room na RoomDetailDTO.
     */
    private function convertRoomToRoomDetailDTO(Room $room) : RoomDetailDTO
    {
        $roomDetailDTO = new RoomDetailDTO();

        $roomDetailDTO->setId($room->getId());
        $roomDetailDTO->setName($room->getName());
        $roomDetailDTO->setCapacity($room->getCapacity());

        return $roomDetailDTO;
    }
}
