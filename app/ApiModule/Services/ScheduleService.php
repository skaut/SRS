<?php

declare(strict_types=1);

namespace App\ApiModule\Services;

use ApiModule\Dto\Schedule\LectorDetailDto;
use ApiModule\Dto\Schedule\RoomDetailDto;
use App\ApiModule\Dto\Schedule\BlockDetailDto;
use App\ApiModule\Dto\Schedule\CalendarConfigDto;
use App\ApiModule\Dto\Schedule\ProgramDetailDto;
use App\ApiModule\Dto\Schedule\ProgramSaveDto;
use App\ApiModule\Dto\Schedule\ResponseDto;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\Program;
use App\Model\Program\ProgramRepository;
use App\Model\Program\Room;
use App\Model\Program\RoomRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ProgramService;
use App\Services\SettingsService;
use DateInterval;
use Exception;
use Nette;
use Nette\Localization\ITranslator;
use Throwable;
use function array_intersect;
use function count;
use function floor;
use function in_array;
use function spl_object_id;
use const DATE_ISO8601;

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

    /** @var ITranslator */
    private $translator;

    /** @var UserRepository */
    private $userRepository;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var BlockRepository */
    private $blockRepository;

    /** @var RoomRepository */
    private $roomRepository;

    /** @var SettingsService */
    private $settingsService;

    /** @var ProgramService */
    private $programService;

    public function __construct(
        ITranslator $translator,
        UserRepository $userRepository,
        ProgramRepository $programRepository,
        BlockRepository $blockRepository,
        RoomRepository $roomRepository,
        SettingsService $settingsService,
        ProgramService $programService
    ) {
        $this->translator        = $translator;
        $this->userRepository    = $userRepository;
        $this->programRepository = $programRepository;
        $this->blockRepository   = $blockRepository;
        $this->roomRepository    = $roomRepository;
        $this->settingsService   = $settingsService;
        $this->programService    = $programService;
    }

    public function setUser(int $userId) : void
    {
        $this->user = $this->userRepository->findById($userId);
    }

    /**
     * Vrací podrobnosti o všech programech pro použití v administraci harmonogramu.
     *
     * @return ProgramDetailDto[]
     *
     * @throws Exception
     */
    public function getProgramsAdmin() : array
    {
        $programs               = $this->programRepository->findAll();
        $programAdminDetailDtos = [];
        foreach ($programs as $program) {
            $programAdminDetailDtos[] = $this->convertProgramToProgramDetailDto($program);
        }

        return $programAdminDetailDtos;
    }

    /**
     * Vrací podrobnosti o programech, ke kterým má uživatel přístup, pro použití v kalendáři pro výběr programů.
     *
     * @return ProgramDetailDto[]
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getProgramsWeb() : array
    {
        $programs          = $this->programService->getUserAllowedPrograms($this->user);
        $programDetailDtos = [];
        foreach ($programs as $program) {
            $programDetailDto = $this->convertProgramToProgramDetailDto($program);
            $programDetailDto->setAttendeesCount($program->getAttendeesCount());
            $programDetailDto->setUserAttends($program->isAttendee($this->user));
            $programDetailDto->setBlocks($this->programRepository->findBlockedProgramsIdsByProgram($program));
            $programDetailDto->setBlocked(false);
            $programDetailDto->setPaid($this->settingsService->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT)
                || ($this->user->hasPaidSubevent($program->getBlock()->getSubevent()) && $this->user->hasPaidRolesApplication()));
            $programDetailDtos[] = $programDetailDto;
        }

        foreach ($programDetailDtos as $p1) {
            foreach ($programDetailDtos as $p2) {
                if (spl_object_id($p1) !== spl_object_id($p2) && $p1->isUserAttends() && in_array($p2->getId(), $p1->getBlocks())) {
                    $p2->setBlocked(true);
                }
            }
        }

        return $programDetailDtos;
    }

    /**
     * Vrací podrobnosti o programových blocích.
     *
     * @return BlockDetailDto[]
     */
    public function getBlocks() : array
    {
        $blocks          = $this->blockRepository->findAll();
        $blockDetailDtos = [];
        foreach ($blocks as $block) {
            $blockDetailDtos[] = $this->convertBlockToBlockDetailDto($block);
        }

        return $blockDetailDtos;
    }

    /**
     * Vrací podrobnosti o místnostech.
     *
     * @return RoomDetailDto[]
     */
    public function getRooms() : array
    {
        $rooms          = $this->roomRepository->findAll();
        $roomDetailDtos = [];
        foreach ($rooms as $room) {
            $roomDetailDtos[] = $this->convertRoomToRoomDetailDto($room);
        }

        return $roomDetailDtos;
    }

    /**
     * Vrací nastavení pro FullCalendar.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getCalendarConfig() : CalendarConfigDto
    {
        $calendarConfigDto = new CalendarConfigDto();

        $fromDate = $this->settingsService->getDateValue(Settings::SEMINAR_FROM_DATE);
        $toDate   = $this->settingsService->getDateValue(Settings::SEMINAR_TO_DATE);

        $calendarConfigDto->setSeminarFromDate($fromDate->format('Y-m-d'));
        $calendarConfigDto->setSeminarDuration($toDate->diff($fromDate)->d + 1);
        $calendarConfigDto->setAllowedModifySchedule(
            $this->settingsService->getBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE) &&
            $this->user->isAllowed(SrsResource::PROGRAM, Permission::MANAGE_SCHEDULE)
        );

        return $calendarConfigDto;
    }

    /**
     * Uloží nebo vytvoří program.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function saveProgram(ProgramSaveDto $programSaveDto) : ResponseDto
    {
        $responseDto = new ResponseDto();
        $responseDto->setStatus('danger');

        $programId = $programSaveDto->getId();
        $block     = $this->blockRepository->findById($programSaveDto->getBlockId());
        $room      = $programSaveDto->getRoomId() ? $this->roomRepository->findById($programSaveDto->getRoomId()) : null;
        $start     = $programSaveDto->getStart();
        $end       = $start->add(new DateInterval('PT' . $block->getDuration() . 'M'));

        $overlappingLecturersProgram = false;
        foreach ($block->getLectors() as $lector) {
            if ($this->userRepository->hasOverlappingLecturersProgram($lector, $programId, $start, $end)) {
                $overlappingLecturersProgram = true;
                break;
            }
        }

        if (! $this->user->isAllowed(SrsResource::PROGRAM, Permission::MANAGE_SCHEDULE)) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_user_not_allowed_manage'));
        } elseif (! $this->settingsService->getBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE)) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_not_allowed_modfify'));
        } elseif ($overlappingLecturersProgram) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_lector_has_another_program'));
        } elseif ($room && $this->roomRepository->hasOverlappingProgram($room, $programId, $start, $end)) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_room_occupied', null, ['name' => $room->getName()]));
        } elseif ($block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED && $this->programRepository->hasOverlappingProgram($programId, $start, $end)) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_auto_registered_not_allowed'));
        } elseif ($this->programRepository->hasOverlappingAutoRegisteredProgram($programId, $start, $end)) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_auto_registered_not_allowed'));
        } else {
            if ($programId) {
                $program = $this->programRepository->findById($programId);
                $this->programService->updateProgram($program, $room, $start);
            } else {
                $program = $this->programService->createProgram($block, $room, $start);
            }

            $responseDto = new ResponseDto();
            $responseDto->setProgram($this->convertProgramToProgramDetailDto($program));
            $responseDto->setMessage($this->translator->translate('common.api.schedule_saved'));
            $responseDto->setStatus('success');
        }

        return $responseDto;
    }

    /**
     * Smaže program.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function removeProgram(int $programId) : ResponseDto
    {
        $program = $this->programRepository->findById($programId);

        $responseDto = new ResponseDto();
        $responseDto->setStatus('danger');

        if (! $this->user->isAllowed(SrsResource::PROGRAM, Permission::MANAGE_SCHEDULE)) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_user_not_allowed_manage'));
        } elseif (! $this->settingsService->getBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE)) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_not_allowed_modfify'));
        } elseif (! $program) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_program_not_found'));
        } else {
            $programDetailDto = new ProgramDetailDto();
            $programDetailDto->setId($program->getId());

            $this->programService->removeProgram($program);

            $responseDto->setProgram($programDetailDto);
            $responseDto->setMessage($this->translator->translate('common.api.schedule_deleted'));
            $responseDto->setStatus('success');
        }

        return $responseDto;
    }

    /**
     * Přihlásí program uživateli.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function attendProgram(int $programId) : ResponseDto
    {
        $program = $this->programRepository->findById($programId);

        $responseDto = new ResponseDto();
        $responseDto->setStatus('danger');

        if (! $this->user->isAllowed(SrsResource::PROGRAM, Permission::CHOOSE_PROGRAMS)) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_user_not_allowed_register_programs'));
        } elseif (! $this->programService->isAllowedRegisterPrograms()) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_register_programs_not_allowed'));
        } elseif (! $this->settingsService->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT) &&
            ! $this->user->hasPaidSubevent($program->getBlock()->getSubevent())
        ) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_register_programs_before_payment_not_allowed'));
        } elseif (! $program) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_program_not_found'));
        } elseif ($this->user->getPrograms()->contains($program)) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_program_already_registered'));
        } elseif ($program->getCapacity() !== null && $program->getCapacity() <= $program->getAttendeesCount()) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_program_no_vacancies'));
        } elseif (! $this->programService->getUserAllowedPrograms($this->user)->contains($program)) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_program_category_not_allowed'));
        } elseif (count(
            array_intersect(
                $this->programRepository->findBlockedProgramsIdsByProgram($program),
                $this->programRepository->findProgramsIds($this->user->getPrograms())
            )
        )) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_program_blocked'));
        } else {
            $this->programService->registerProgram($this->user, $program);

            $responseDto->setMessage($this->translator->translate('common.api.program_registered'));
            $responseDto->setStatus('success');

            $programDetailDto = $this->convertProgramToProgramDetailDto($program);
            $programDetailDto->setAttendeesCount($program->getAttendeesCount());

            $responseDto->setProgram($programDetailDto);
        }

        return $responseDto;
    }

    /**
     * Odhlásí program uživateli.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function unattendProgram(int $programId) : ResponseDto
    {
        $program = $this->programRepository->findById($programId);

        $responseDto = new ResponseDto();
        $responseDto->setStatus('danger');

        if (! $this->programService->isAllowedRegisterPrograms()) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_register_programs_not_allowed'));
        } elseif (! $program) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_program_not_found'));
        } elseif (! $this->user->getPrograms()->contains($program)) {
            $responseDto->setMessage($this->translator->translate('common.api.schedule_program_not_registered'));
        } else {
            $this->programService->unregisterProgram($this->user, $program);

            $responseDto->setMessage($this->translator->translate('common.api.program_unregistered'));
            $responseDto->setStatus('success');

            $programDetailDto = $this->convertProgramToProgramDetailDto($program);
            $programDetailDto->setAttendeesCount($program->getAttendeesCount());

            $responseDto->setProgram($programDetailDto);
        }

        return $responseDto;
    }

    /**
     * Převede Program na ProgramDetailDto.
     *
     * @throws Exception
     */
    private function convertProgramToProgramDetailDto(Program $program) : ProgramDetailDto
    {
        $programDetailDto = new ProgramDetailDto();

        $programDetailDto->setId($program->getId());
        $programDetailDto->setTitle($program->getBlock()->getName());
        $programDetailDto->setStart($program->getStart()->format(DATE_ISO8601));
        $programDetailDto->setEnd($program->getEnd()->format(DATE_ISO8601));
        $programDetailDto->setBlockId($program->getBlock()->getId());
        $programDetailDto->setRoomId($program->getRoom() ? $program->getRoom()->getId() : null);

        return $programDetailDto;
    }

    /**
     * Převede Block na BlockDetailDto.
     */
    private function convertBlockToBlockDetailDto(Block $block) : BlockDetailDto
    {
        $blockDetailDto = new BlockDetailDto();

        $blockDetailDto->setId($block->getId());
        $blockDetailDto->setName($block->getName());
        $blockDetailDto->setCategory($block->getCategory() ? $block->getCategory()->getName() : '');
        $blockDetailDto->setLectors($block->getLectors()->map(function (User $lector) {
            return $this->convertUserToLectorDetailDto($lector);
        })->toArray());
        $blockDetailDto->setLectorsNames($block->getLectorsText());
        $blockDetailDto->setDurationHours((int) floor($block->getDuration() / 60));
        $blockDetailDto->setDurationMinutes($block->getDuration() % 60);
        $blockDetailDto->setCapacity($block->getCapacity());
        $blockDetailDto->setMandatory($block->getMandatory() === ProgramMandatoryType::MANDATORY || $block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED);
        $blockDetailDto->setAutoRegistered($block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED);
        $blockDetailDto->setPerex($block->getPerex());
        $blockDetailDto->setDescription($block->getDescription());
        $blockDetailDto->setProgramsCount($block->getProgramsCount());
        $blockDetailDto->setUserAttends($block->isAttendee($this->user));
        $blockDetailDto->setUserAllowed($block->isAllowed($this->user));

        return $blockDetailDto;
    }

    /**
     * Převede User na LectorDetailDto.
     */
    private function convertUserToLectorDetailDto(User $lector) : LectorDetailDto
    {
        $lectorDetailDto = new LectorDetailDto();

        $lectorDetailDto->setId($lector->getId());
        $lectorDetailDto->setName($lector->getLectorName());
        $lectorDetailDto->setAbout($lector->getAbout());
        $lectorDetailDto->setPhoto($lector->getPhoto());

        return $lectorDetailDto;
    }

    /**
     * Převede Room na RoomDetailDto.
     */
    private function convertRoomToRoomDetailDto(Room $room) : RoomDetailDto
    {
        $roomDetailDto = new RoomDetailDto();

        $roomDetailDto->setId($room->getId());
        $roomDetailDto->setName($room->getName());
        $roomDetailDto->setCapacity($room->getCapacity());

        return $roomDetailDto;
    }
}
