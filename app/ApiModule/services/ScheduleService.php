<?php

declare(strict_types=1);

namespace App\ApiModule\Services;

use ApiModule\DTO\Schedule\LectorDetailDTO;
use ApiModule\DTO\Schedule\RoomDetailDTO;
use App\ApiModule\DTO\Schedule\BlockDetailDTO;
use App\ApiModule\DTO\Schedule\CalendarConfigDTO;
use App\ApiModule\DTO\Schedule\ProgramDetailDTO;
use App\ApiModule\DTO\Schedule\ProgramSaveDTO;
use App\ApiModule\DTO\Schedule\ResponseDTO;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\Program;
use App\Model\Program\ProgramRepository;
use App\Model\Program\Room;
use App\Model\Program\RoomRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ProgramService;
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

    /** @var SettingsFacade */
    private $settingsFacade;

    /** @var ProgramService */
    private $programService;

    public function __construct(
        Translator $translator,
        UserRepository $userRepository,
        ProgramRepository $programRepository,
        BlockRepository $blockRepository,
        RoomRepository $roomRepository,
        SettingsFacade $settingsFacade,
        ProgramService $programService
    ) {
        $this->translator        = $translator;
        $this->userRepository    = $userRepository;
        $this->programRepository = $programRepository;
        $this->blockRepository   = $blockRepository;
        $this->roomRepository    = $roomRepository;
        $this->settingsFacade    = $settingsFacade;
        $this->programService    = $programService;
    }

    public function setUser(int $userId) : void
    {
        $this->user = $this->userRepository->findById($userId);
    }

    /**
     * Vrací podrobnosti o všech programech pro použití v administraci harmonogramu.
     *
     * @return ProgramDetailDTO[]
     * @throws \Exception
     */
    public function getProgramsAdmin() : array
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
     *
     * @return ProgramDetailDTO[]
     * @throws SettingsException
     * @throws \Throwable
     */
    public function getProgramsWeb() : array
    {
        $programs          = $this->programService->getUserAllowedPrograms($this->user);
        $programDetailDTOs = [];
        foreach ($programs as $program) {
            $programDetailDTO = $this->convertProgramToProgramDetailDTO($program);
            $programDetailDTO->setAttendeesCount($program->getAttendeesCount());
            $programDetailDTO->setUserAttends($program->isAttendee($this->user));
            $programDetailDTO->setBlocks($this->programRepository->findBlockedProgramsIdsByProgram($program));
            $programDetailDTO->setBlocked(false);
            $programDetailDTO->setPaid($this->settingsFacade->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT) || ($this->user->hasPaidSubevent($program->getBlock()->getSubevent()) && $this->user->hasPaidRolesApplication()));
            $programDetailDTOs[] = $programDetailDTO;
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
     *
     * @return BlockDetailDTO[]
     */
    public function getBlocks() : array
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
     *
     * @return RoomDetailDTO[]
     */
    public function getRooms() : array
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
     *
     * @throws SettingsException
     * @throws \Throwable
     */
    public function getCalendarConfig() : CalendarConfigDTO
    {
        $calendarConfigDTO = new CalendarConfigDTO();

        $fromDate = $this->settingsFacade->getDateValue(Settings::SEMINAR_FROM_DATE);
        $toDate   = $this->settingsFacade->getDateValue(Settings::SEMINAR_TO_DATE);

        $calendarConfigDTO->setSeminarFromDate($fromDate->format('Y-m-d'));
        $calendarConfigDTO->setSeminarDuration($toDate->diff($fromDate)->d + 1);
        $calendarConfigDTO->setAllowedModifySchedule(
            $this->settingsFacade->getBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE) &&
                $this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE)
        );

        return $calendarConfigDTO;
    }

    /**
     * Uloží nebo vytvoří program.
     *
     * @throws SettingsException
     * @throws \Throwable
     */
    public function saveProgram(ProgramSaveDTO $programSaveDTO) : ResponseDTO
    {
        $responseDTO = new ResponseDTO();
        $responseDTO->setStatus('danger');

        $programId = $programSaveDTO->getId();
        $block     = $this->blockRepository->findById($programSaveDTO->getBlockId());
        $room      = $programSaveDTO->getRoomId() ? $this->roomRepository->findById($programSaveDTO->getRoomId()) : null;
        $start     = $programSaveDTO->getStart();
        $end       = clone $start;
        $end->add(new \DateInterval('PT' . $block->getDuration() . 'M'));

        if (! $this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_user_not_allowed_manage'));
        } elseif (! $this->settingsFacade->getBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_not_allowed_modfify'));
        } elseif ($room && $this->roomRepository->hasOverlappingProgram($room, $programId, $start, $end)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_room_occupied', null, ['name' => $room->getName()]));
        } elseif ($block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED && $this->programRepository->hasOverlappingProgram($programId, $start, $end)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_auto_registered_not_allowed'));
        } elseif ($this->programRepository->hasOverlappingAutoRegisteredProgram($programId, $start, $end)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_auto_registered_not_allowed'));
        } else {
            if ($programId) {
                $program = $this->programRepository->findById($programId);
                $this->programService->updateProgram($program, $room, $start);
            } else {
                $program = $this->programService->createProgram($block, $room, $start);
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
     *
     * @throws SettingsException
     * @throws \Throwable
     */
    public function removeProgram(int $programId) : ResponseDTO
    {
        $program = $this->programRepository->findById($programId);

        $responseDTO = new ResponseDTO();
        $responseDTO->setStatus('danger');

        if (! $this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_user_not_allowed_manage'));
        } elseif (! $this->settingsFacade->getBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_not_allowed_modfify'));
        } elseif (! $program) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_not_found'));
        } else {
            $programDetailDTO = new ProgramDetailDTO();
            $programDetailDTO->setId($program->getId());

            $this->programService->removeProgram($program);

            $responseDTO->setProgram($programDetailDTO);
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_deleted'));
            $responseDTO->setStatus('success');
        }

        return $responseDTO;
    }

    /**
     * Přihlásí program uživateli.
     *
     * @throws SettingsException
     * @throws \Throwable
     */
    public function attendProgram(int $programId) : ResponseDTO
    {
        $program = $this->programRepository->findById($programId);

        $responseDTO = new ResponseDTO();
        $responseDTO->setStatus('danger');

        if (! $this->user->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS)) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_user_not_allowed_register_programs'));
        } elseif (! $this->programService->isAllowedRegisterPrograms()) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_register_programs_not_allowed'));
        } elseif (! $this->settingsFacade->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT) && ! $this->user->hasPaidSubevent($program->getBlock()->getSubevent())
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
        )
        ) {
            $responseDTO->setMessage($this->translator->translate('common.api.schedule_program_blocked'));
        } else {
            $this->programService->registerProgram($this->user, $program);

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
     *
     * @throws SettingsException
     * @throws \Throwable
     */
    public function unattendProgram(int $programId) : ResponseDTO
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
            $this->programService->unregisterProgram($this->user, $program);

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
     *
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
        $blockDetailDTO->setLectors(
            $block->getLectors()->map(
                function (User $lector) {
                            return $this->convertUserToLectorDetailDTO($lector);
                }
            )->toArray()
        );
        $blockDetailDTO->setLectorsNames($block->getLectorsText());
        $blockDetailDTO->setDurationHours((int) floor($block->getDuration() / 60));
        $blockDetailDTO->setDurationMinutes($block->getDuration() % 60);
        $blockDetailDTO->setCapacity($block->getCapacity());
        $blockDetailDTO->setMandatory($block->getMandatory() === ProgramMandatoryType::MANDATORY || $block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED);
        $blockDetailDTO->setAutoRegistered($block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED);
        $blockDetailDTO->setPerex($block->getPerex());
        $blockDetailDTO->setDescription($block->getDescription());
        $blockDetailDTO->setProgramsCount($block->getProgramsCount());
        $blockDetailDTO->setUserAttends($block->isAttendee($this->user));
        $blockDetailDTO->setUserAllowed($block->isAllowed($this->user));

        return $blockDetailDTO;
    }

    /**
     * Převede User na LectorDetailDTO.
     */
    private function convertUserToLectorDetailDTO(User $lector) : LectorDetailDTO
    {
        $lectorDetailDTO = new LectorDetailDTO();

        $lectorDetailDTO->setId($lector->getId());
        $lectorDetailDTO->setName($lector->getLectorName());
        $lectorDetailDTO->setAbout($lector->getAbout());
        $lectorDetailDTO->setPhoto($lector->getPhoto());

        return $lectorDetailDTO;
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
