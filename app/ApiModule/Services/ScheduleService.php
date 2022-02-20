<?php

declare(strict_types=1);

namespace App\ApiModule\Services;

use App\ApiModule\Dto\Schedule\BlockDetailDto;
use App\ApiModule\Dto\Schedule\CalendarConfigDto;
use App\ApiModule\Dto\Schedule\LectorDetailDto;
use App\ApiModule\Dto\Schedule\ProgramDetailDto;
use App\ApiModule\Dto\Schedule\ProgramSaveDto;
use App\ApiModule\Dto\Schedule\ResponseDto;
use App\ApiModule\Dto\Schedule\RoomDetailDto;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\Commands\RemoveProgram;
use App\Model\Program\Commands\SaveProgram;
use App\Model\Program\Exceptions\ProgramCapacityOccupiedException;
use App\Model\Program\Exceptions\UserAlreadyAttendsBlockException;
use App\Model\Program\Exceptions\UserAlreadyAttendsProgramException;
use App\Model\Program\Exceptions\UserAttendsConflictingProgramException;
use App\Model\Program\Exceptions\UserNotAllowedProgramException;
use App\Model\Program\Exceptions\UserNotAttendsProgramException;
use App\Model\Program\Program;
use App\Model\Program\Queries\ProgramAlternatesQuery;
use App\Model\Program\Queries\ProgramAttendeesQuery;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Program\Repositories\RoomRepository;
use App\Model\Program\Room;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\IsAllowedRegisterProgramsQuery;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Queries\SettingDateValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Queries\UserAllowedBlocksQuery;
use App\Model\User\Queries\UserAllowedProgramsQuery;
use App\Model\User\Queries\UserAttendsBlocksQuery;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\CommandBus;
use App\Services\QueryBus;
use App\Utils\Helpers;
use DateInterval;
use Doctrine\ORM\ORMException;
use Exception;
use Nette;
use Nette\Localization\Translator;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

use function in_array;

use const DATE_ISO8601;

/**
 * Služba pro zpracování požadavků z API pro správu harmonogramu a zapisování programů.
 */
class ScheduleService
{
    use Nette\SmartObject;

    private ?User $user = null;

    private Translator $translator;

    private UserRepository $userRepository;

    private ProgramRepository $programRepository;

    private BlockRepository $blockRepository;

    private RoomRepository $roomRepository;

    private CommandBus $commandBus;

    private QueryBus $queryBus;

    public function __construct(
        Translator $translator,
        UserRepository $userRepository,
        ProgramRepository $programRepository,
        BlockRepository $blockRepository,
        RoomRepository $roomRepository,
        CommandBus $commandBus,
        QueryBus $queryBus
    ) {
        $this->translator        = $translator;
        $this->userRepository    = $userRepository;
        $this->programRepository = $programRepository;
        $this->blockRepository   = $blockRepository;
        $this->roomRepository    = $roomRepository;
        $this->commandBus        = $commandBus;
        $this->queryBus          = $queryBus;
    }

    public function setUser(int $userId): void
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
    public function getProgramsAdmin(): array
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
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function getProgramsWeb(): array
    {
        $registrationBeforePaymentAllowed = $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT));
        $userAllowedPrograms              = $this->queryBus->handle(new UserAllowedProgramsQuery($this->user, ! $registrationBeforePaymentAllowed));
        $userAllowedProgramsWithNotPaid   = $this->queryBus->handle(new UserAllowedProgramsQuery($this->user, false));

        $programDetailDtos = [];
        foreach ($userAllowedProgramsWithNotPaid as $program) {
            $programAttendees  = $this->queryBus->handle(new ProgramAttendeesQuery($program));
            $programAlternates = $this->queryBus->handle(new ProgramAlternatesQuery($program));

            $programDetailDto = $this->convertProgramToProgramDetailDto($program);
            $programDetailDto->setAttendeesCount($programAttendees->count());
            $programDetailDto->setAlternatesCount($programAlternates->count());
            $programDetailDto->setUserAttends($programAttendees->contains($this->user));
            $programDetailDto->setUserAlternates($programAlternates->contains($this->user));
            $programDetailDto->setSameBlockPrograms(Helpers::getIds($this->programRepository->findSameBlockPrograms($program)));
            $programDetailDto->setOverlappingPrograms(Helpers::getIds($this->programRepository->findOverlappingPrograms($program)));
            $programDetailDto->setBlocked(false);
            $programDetailDto->setPaid($userAllowedPrograms->contains($program));
            $programDetailDtos[] = $programDetailDto;
        }

        foreach ($programDetailDtos as $p1) {
            foreach ($programDetailDtos as $p2) {
                if ($p1->getId() !== $p2->getId()) {
                    if (
                        (($p1->getUserAttends() || $p1->getUserAlternates()) && in_array($p2->getId(), $p1->getOverlappingPrograms()))
                        || ($p1->getUserAttends() && in_array($p2->getId(), $p1->getSameBlockPrograms()))
                    ) {
                        $p2->setBlocked(true);
                    }
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
    public function getBlocks(): array
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
    public function getRooms(): array
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
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function getCalendarConfig(): CalendarConfigDto
    {
        $calendarConfigDto = new CalendarConfigDto();

        $fromDate = $this->queryBus->handle(new SettingDateValueQuery(Settings::SEMINAR_FROM_DATE));
        $toDate   = $this->queryBus->handle(new SettingDateValueQuery(Settings::SEMINAR_TO_DATE));

        $calendarConfigDto->setSeminarFromDate($fromDate->format('Y-m-d'));
        $calendarConfigDto->setSeminarToDate($toDate->add(new DateInterval('P1D'))->format('Y-m-d'));
        $calendarConfigDto->setAllowedModifySchedule($this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_MODIFY_SCHEDULE))
            && $this->user->isAllowed(SrsResource::PROGRAM, Permission::MANAGE_SCHEDULE));

        $programs = $this->programRepository->findAll();
        if (empty($programs)) {
            $minTime = 0;
            $maxTime = 24;
        } else {
            $minTime = 24;
            $maxTime = 0;
            foreach ($programs as $program) {
                $start = (int) $program->getStart()->format('H');
                if ($start < $minTime) {
                    $minTime = $start;
                }

                $end = (int) $program->getEnd()->format('H');
                if ((int) $program->getEnd()->format('i') > 0) {
                    $end++;
                }

                if ($end > $maxTime) {
                    $maxTime = $end;
                }
            }
        }

        $calendarConfigDto->setMinTime((string) $minTime);
        $calendarConfigDto->setMaxTime((string) $maxTime);

        $calendarConfigDto->setInitialView($this->queryBus->handle(new SettingStringValueQuery(Settings::SCHEDULE_INITIAL_VIEW)));

        return $calendarConfigDto;
    }

    /**
     * Uloží nebo vytvoří program.
     *
     * @throws ApiException
     * @throws Throwable
     */
    public function saveProgram(ProgramSaveDto $programSaveDto): ResponseDto
    {
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
            throw new ApiException($this->translator->translate('api.schedule.user_not_allowed_manage'));
        } elseif (! $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_MODIFY_SCHEDULE))) {
            throw new ApiException($this->translator->translate('api.schedule.not_allowed_modify'));
        } elseif ($overlappingLecturersProgram) {
            throw new ApiException($this->translator->translate('api.schedule.lector_has_another_program'));
        } elseif ($room && $this->roomRepository->hasOverlappingProgram($room, $programId, $start, $end)) {
            throw new ApiException($this->translator->translate('api.schedule.room_occupied', null, ['name' => $room->getName()]));
        } elseif ($block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED && $this->programRepository->hasOverlappingProgram($programId, $start, $end)) {
            throw new ApiException($this->translator->translate('api.schedule.auto_registered_not_allowed'));
        } elseif ($this->programRepository->hasOverlappingAutoRegisteredProgram($programId, $start, $end)) {
            throw new ApiException($this->translator->translate('api.schedule.auto_registered_not_allowed'));
        } else {
            $program = null;

            if ($programId === null) {
                $program = new Program($start);
                $program->setBlock($block);
            } else {
                $program = $this->programRepository->findById($programId);
                $program->setStart($start);
            }

            $program->setRoom($room);

            $this->commandBus->handle(new SaveProgram($program));

            $responseDto = new ResponseDto();
            $responseDto->setProgram($this->convertProgramToProgramDetailDto($program));

            if ($room !== null && $room->getCapacity() !== null && $block->getCapacity() !== null && $room->getCapacity() < $block->getCapacity()) {
                $responseDto->setMessage($this->translator->translate('api.schedule.saved_room_capacity'));
                $responseDto->setStatus('warning');
            } else {
                $responseDto->setMessage($this->translator->translate('api.schedule.saved'));
                $responseDto->setStatus('success');
            }
        }

        return $responseDto;
    }

    /**
     * Smaže program.
     *
     * @throws ApiException
     * @throws Throwable
     */
    public function removeProgram(int $programId): ResponseDto
    {
        $program = $this->programRepository->findById($programId);

        if (! $this->user->isAllowed(SrsResource::PROGRAM, Permission::MANAGE_SCHEDULE)) {
            throw new ApiException($this->translator->translate('api.schedule.user_not_allowed_manage'));
        } elseif (! $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_MODIFY_SCHEDULE))) {
            throw new ApiException($this->translator->translate('api.schedule.not_allowed_modify'));
        } elseif (! $program) {
            throw new ApiException($this->translator->translate('api.schedule.program_not_found'));
        } else {
            $programDetailDto = new ProgramDetailDto();
            $programDetailDto->setId($program->getId());

            $this->commandBus->handle(new RemoveProgram($program));

            $responseDto = new ResponseDto();
            $responseDto->setProgram($programDetailDto);
            $responseDto->setMessage($this->translator->translate('api.schedule.deleted'));
            $responseDto->setStatus('success');
        }

        return $responseDto;
    }

    /**
     * Přihlásí program uživateli.
     *
     * @throws ApiException
     * @throws Throwable
     */
    public function attendProgram(int $programId): ResponseDto
    {
        $program = $this->programRepository->findById($programId);

        if (! $program) {
            throw new ApiException($this->translator->translate('api.schedule.program_not_found'));
        }

        if (! $this->queryBus->handle(new IsAllowedRegisterProgramsQuery())) {
            throw new ApiException($this->translator->translate('api.schedule.register_programs_not_allowed'));
        }

        try {
            $this->commandBus->handle(new RegisterProgram($this->user, $program, false));

            $responseDto = new ResponseDto();
            $responseDto->setMessage($this->translator->translate('api.schedule.program_registered'));
            $responseDto->setStatus('success');

            $programAttendees  = $this->queryBus->handle(new ProgramAttendeesQuery($program));
            $programAlternates = $this->queryBus->handle(new ProgramAlternatesQuery($program));

            $programDetailDto = $this->convertProgramToProgramDetailDto($program);
            $programDetailDto->setAttendeesCount($programAttendees->count());
            $programDetailDto->setAlternatesCount($programAlternates->count());
            $programDetailDto->setUserAttends($programAttendees->contains($this->user));
            $programDetailDto->setUserAlternates($programAlternates->contains($this->user));

            $responseDto->setProgram($programDetailDto);

            return $responseDto;
        } catch (HandlerFailedException $e) {
            if ($e->getPrevious() instanceof UserNotAllowedProgramException) {
                throw new ApiException($this->translator->translate('api.schedule.program_not_allowed'));
            }

            if ($e->getPrevious() instanceof UserAlreadyAttendsProgramException) {
                throw new ApiException($this->translator->translate('api.schedule.program_already_registered'));
            }

            if ($e->getPrevious() instanceof UserAlreadyAttendsBlockException) {
                throw new ApiException($this->translator->translate('api.schedule.program_blocked'));
            }

            if ($e->getPrevious() instanceof ProgramCapacityOccupiedException) {
                throw new ApiException($this->translator->translate('api.schedule.program_no_vacancies'));
            }

            if ($e->getPrevious() instanceof UserAttendsConflictingProgramException) {
                throw new ApiException($this->translator->translate('api.schedule.program_blocked'));
            }

            throw $e;
        }
    }

    /**
     * Odhlásí program uživateli.
     *
     * @throws ApiException
     * @throws Throwable
     */
    public function unattendProgram(int $programId): ResponseDto
    {
        $program = $this->programRepository->findById($programId);

        if (! $program) {
            throw new ApiException($this->translator->translate('api.schedule.program_not_found'));
        }

        if (! $this->queryBus->handle(new IsAllowedRegisterProgramsQuery())) {
            throw new ApiException($this->translator->translate('api.schedule.register_programs_not_allowed'));
        }

        try {
            $this->commandBus->handle(new UnregisterProgram($this->user, $program, false));

            $responseDto = new ResponseDto();
            $responseDto->setMessage($this->translator->translate('api.schedule.program_unregistered'));
            $responseDto->setStatus('success');

            $programAttendees  = $this->queryBus->handle(new ProgramAttendeesQuery($program));
            $programAlternates = $this->queryBus->handle(new ProgramAlternatesQuery($program));

            $programDetailDto = $this->convertProgramToProgramDetailDto($program);
            $programDetailDto->setAttendeesCount($programAttendees->count());
            $programDetailDto->setAlternatesCount($programAlternates->count());
            $programDetailDto->setUserAttends($programAttendees->contains($this->user));
            $programDetailDto->setUserAlternates($programAlternates->contains($this->user));

            $responseDto->setProgram($programDetailDto);

            return $responseDto;
        } catch (HandlerFailedException $e) {
            if ($e->getPrevious() instanceof UserNotAttendsProgramException) {
                throw new ApiException($this->translator->translate('api.schedule.program_not_registered'));
            }

            throw $e;
        }
    }

    /**
     * Převede Program na ProgramDetailDto.
     *
     * @throws Exception
     */
    private function convertProgramToProgramDetailDto(Program $program): ProgramDetailDto
    {
        $programDetailDto = new ProgramDetailDto();

        $programDetailDto->setId($program->getId());
        $programDetailDto->setStart($program->getStart()->format(DATE_ISO8601));
        $programDetailDto->setEnd($program->getEnd()->format(DATE_ISO8601));
        $programDetailDto->setBlockId($program->getBlock()->getId());
        $programDetailDto->setRoomId($program->getRoom() ? $program->getRoom()->getId() : null);

        return $programDetailDto;
    }

    /**
     * Převede Block na BlockDetailDto.
     */
    private function convertBlockToBlockDetailDto(Block $block): BlockDetailDto
    {
        $registrationBeforePaymentAllowed = $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT));
        $userBlocks                       = $this->queryBus->handle(new UserAttendsBlocksQuery($this->user));
        $userAllowedBlocks                = $this->queryBus->handle(new UserAllowedBlocksQuery($this->user, ! $registrationBeforePaymentAllowed));

        $blockDetailDto = new BlockDetailDto();

        $blockDetailDto->setId($block->getId());
        $blockDetailDto->setName($block->getName());
        $blockDetailDto->setCategory($block->getCategory() ? $block->getCategory()->getName() : '');
        $blockDetailDto->setLectors($block->getLectors()->map(function (User $lector) {
            return $this->convertUserToLectorDetailDto($lector);
        })->toArray());
        $blockDetailDto->setLectorsNames($block->getLectorsText());
        $blockDetailDto->setDuration($block->getDuration());
        $blockDetailDto->setCapacity($block->getCapacity());
        $blockDetailDto->setAlternatesAllowed($block->isAlternatesAllowed());
        $blockDetailDto->setMandatory($block->getMandatory() === ProgramMandatoryType::MANDATORY || $block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED);
        $blockDetailDto->setAutoRegistered($block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED);
        $blockDetailDto->setPerex($block->getPerex());
        $blockDetailDto->setDescription($block->getDescription());
        $blockDetailDto->setProgramsCount($block->getProgramsCount());
        $blockDetailDto->setUserAttends($userBlocks->contains($block));
        $blockDetailDto->setUserAllowed($userAllowedBlocks->contains($block));

        return $blockDetailDto;
    }

    /**
     * Převede User na LectorDetailDto.
     */
    private function convertUserToLectorDetailDto(User $lector): LectorDetailDto
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
    private function convertRoomToRoomDetailDto(Room $room): RoomDetailDto
    {
        $roomDetailDto = new RoomDetailDto();

        $roomDetailDto->setId($room->getId());
        $roomDetailDto->setName($room->getName());
        $roomDetailDto->setCapacity($room->getCapacity());

        return $roomDetailDto;
    }
}
