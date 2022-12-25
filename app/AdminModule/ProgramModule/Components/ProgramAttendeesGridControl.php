<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;

use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Exceptions\ProgramCapacityOccupiedException;
use App\Model\Program\Exceptions\UserAlreadyAttendsBlockException;
use App\Model\Program\Exceptions\UserAlreadyAttendsProgramException;
use App\Model\Program\Exceptions\UserAttendsConflictingProgramException;
use App\Model\Program\Exceptions\UserNotAllowedProgramException;
use App\Model\Program\Exceptions\UserNotAttendsProgramException;
use App\Model\Program\Program;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Queries\UserRegisteredProgramAtQuery;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\CommandBus;
use App\Services\QueryBus;
use App\Utils\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Localization\Translator;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu účastníků programu.
 */
class ProgramAttendeesGridControl extends Control
{
    /**
     * Aktuální program.
     */
    private Program $program;

    private SessionSection $sessionSection;

    public function __construct(
        private Translator $translator,
        private ProgramRepository $programRepository,
        private UserRepository $userRepository,
        Session $session,
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private EntityManagerInterface $em
    ) {
        $this->sessionSection = $session->getSection('srs');
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/program_attendees_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridException
     */
    public function createComponentProgramAttendeesGrid(string $name): void
    {
        $programId = (int) $this->getPresenter()->getParameter('programId');
        if (! $programId) {
            $programId = $this->sessionSection->programId;
        }

        $program = $this->programRepository->findById($programId);

        $grid = new DataGrid($this, $name);

        if (! $program) {
            $grid->setDataSource([]);
        } else {
            $this->program                    = $program;
            $user                             = $this->userRepository->findById($this->getPresenter()->getUser()->getId());
            $registrationBeforePaymentAllowed = $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT));

            $grid->setTranslator($this->translator);

            $qb = $this->userRepository->blockAllowedQuery($program->getBlock(), ! $registrationBeforePaymentAllowed)
                ->leftJoin('u.programApplications', 'pa', 'WITH', 'pa.program = :program')
                ->setParameter('program', $program);

            $grid->setDataSource($qb);
            $grid->setDefaultSort(['displayName' => 'ASC']);
            $grid->setItemsPerPageList([25, 50, 100, 250, 500]);
            $grid->setStrictSessionFilterValues(false);

            $grid->addColumnText('displayName', 'admin.program.blocks.attendees.column.name')
                ->setFilterText();

            $grid->addColumnText('attends', 'admin.program.blocks.attendees.column.attends')
                ->setRenderer(fn (User $user) => $user->isAttendee($this->program)
                    ? $this->translator->translate('admin.common.yes')
                    : $this->translator->translate('admin.common.no'))
                ->setFilterSelect(['' => 'admin.common.all', 'yes' => 'admin.common.yes', 'no' => 'admin.common.no'])
                ->setCondition(static function (QueryBuilder $qb, string $value): void {
                    if ($value === '') {
                        return;
                    } elseif ($value === 'yes') {
                        $qb->andWhere('pa.alternate = false');
                    } elseif ($value === 'no') {
                        $qb->andWhere('pa IS NULL OR pa.alternate = true');
                    }
                })
                ->setTranslateOptions();

            $grid->addColumnText('alternates', 'admin.program.blocks.attendees.column.alternates')
                ->setRenderer(fn (User $user) => $user->isAlternate($this->program)
                    ? $this->translator->translate('admin.common.yes')
                    : $this->translator->translate('admin.common.no'))
                ->setFilterSelect(['' => 'admin.common.all', 'yes' => 'admin.common.yes', 'no' => 'admin.common.no'])
                ->setCondition(static function (QueryBuilder $qb, string $value): void {
                    if ($value === '') {
                        return;
                    } elseif ($value === 'yes') {
                        $qb->andWhere('pa.alternate = true');
                    } elseif ($value === 'no') {
                        $qb->andWhere('pa IS NULL OR pa.alternate = true');
                    }
                })
                ->setTranslateOptions();

            $grid->addColumnDateTime('registeredAt', 'admin.program.blocks.attendees.column.registered_at')
                ->setRenderer(function (User $user) {
                    $registeredAt = $this->queryBus->handle(new UserRegisteredProgramAtQuery($user, $this->program));

                    return $registeredAt === null ? null : $registeredAt->format(Helpers::DATETIME_FORMAT);
                });

            $grid->setDefaultFilter(['attends' => 'yes'], false);

            if ($user->isAllowed(SrsResource::USERS, Permission::MANAGE)) {
                $grid->addAction('detail', 'admin.common.detail', ':Admin:Users:Users:detail')
                    ->setClass('btn btn-xs btn-primary')
                    ->addAttributes(['target' => '_blank']);
            }

            if ($user->isAllowedModifyBlock($this->program->getBlock()) && $program->getBlock()->getMandatory() !== ProgramMandatoryType::AUTO_REGISTERED) {
                $grid->addAction('register', 'admin.program.blocks.attendees.action.register', 'register!')
                    ->setClass('btn btn-xs btn-success ajax');
                $grid->allowRowsAction('register', function (User $user) {
                    $freeCapacity = $this->program->getBlockCapacity() === null || $this->program->getBlockCapacity() > $this->program->getAttendeesCount();

                    return $freeCapacity && ! $user->isAttendee($this->program);
                });

                $grid->addAction('registerAlternate', 'admin.program.blocks.attendees.action.register_alternate', 'register!')
                    ->setClass('btn btn-xs btn-success ajax');
                $grid->allowRowsAction('registerAlternate', function (User $user) {
                    $freeCapacity = $this->program->getBlockCapacity() === null || $this->program->getBlockCapacity() > $this->program->getAttendeesCount();

                    return ! $freeCapacity && $this->program->getBlock()->isAlternatesAllowed()
                        && ! $user->isAttendee($this->program) && ! $user->isAlternate($this->program);
                });

                $grid->addAction('unregister', 'admin.program.blocks.attendees.action.unregister', 'unregister!')
                    ->setClass('btn btn-xs btn-danger ajax');
                $grid->allowRowsAction('unregister', fn (User $user) => $user->isAttendee($this->program) || $user->isAlternate($this->program));

                $grid->addGroupAction('admin.program.blocks.attendees.action.register')->onSelect[]   = [$this, 'groupRegister'];
                $grid->addGroupAction('admin.program.blocks.attendees.action.unregister')->onSelect[] = [$this, 'groupUnregister'];
            }
        }
    }

    /**
     * Přihlásí uživatele na program.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function handleRegister(int $id): void
    {
        $user = $this->userRepository->findById($id);

        $p = $this->getPresenter();

        $program = $this->programRepository->findById($this->sessionSection->programId);

        if (! $this->isAllowedModifyProgram($program)) {
            $p->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
        } else {
            try {
                $this->commandBus->handle(new RegisterProgram($user, $program));
                $p->flashMessage('admin.program.blocks.attendees.message.registered', 'success');
            } catch (HandlerFailedException $e) {
                if ($e->getPrevious() instanceof UserNotAllowedProgramException) {
                    $p->flashMessage('admin.program.blocks.attendees.message.not_allowed', 'danger');
                }

                if ($e->getPrevious() instanceof UserAlreadyAttendsProgramException) {
                    $p->flashMessage('admin.program.blocks.attendees.message.already_attends_program', 'danger');
                }

                if ($e->getPrevious() instanceof UserAlreadyAttendsBlockException) {
                    $p->flashMessage('admin.program.blocks.attendees.message.alreadu_attends_block', 'danger');
                }

                if ($e->getPrevious() instanceof ProgramCapacityOccupiedException) {
                    $p->flashMessage('admin.program.blocks.attendees.message.capacity_occupied', 'danger');
                }

                if ($e->getPrevious() instanceof UserAttendsConflictingProgramException) {
                    $p->flashMessage('admin.program.blocks.attendees.message.attends_conflicting', 'danger');
                }
            }
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $p->redrawControl('programs');
        } else {
            $p->redirect('this');
        }
    }

    /**
     * Odhlásí uživatele z programu.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function handleUnregister(int $id): void
    {
        $user = $this->userRepository->findById($id);

        $p = $this->getPresenter();

        $program = $this->programRepository->findById($this->sessionSection->programId);

        if (! $this->isAllowedModifyProgram($program)) {
            $p->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
        } else {
            try {
                $this->commandBus->handle(new UnregisterProgram($user, $program));
                $p->flashMessage('admin.program.blocks.attendees.message.unregistered', 'success');
            } catch (HandlerFailedException $e) {
                if ($e->getPrevious() instanceof UserNotAttendsProgramException) {
                    $p->flashMessage('admin.program.blocks.attendees.message.not_attends', 'danger');
                }
            }
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $p->redrawControl('programs');
        } else {
            $p->redirect('this');
        }
    }

    /**
     * Hromadně přihlásí program uživatelům.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function groupRegister(array $ids): void
    {
        $p = $this->getPresenter();

        if (! $this->isAllowedModifyProgram($this->program)) {
            $p->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
        } else {
            try {
                $this->em->wrapInTransaction(function () use ($ids): void {
                    foreach ($ids as $id) {
                        $user = $this->userRepository->findById($id);
                        $this->commandBus->handle(new RegisterProgram($user, $this->program));
                    }
                });
                $p->flashMessage('admin.program.blocks.attendees.message.group_register_success', 'success');
            } catch (HandlerFailedException) {
                $p->flashMessage('admin.program.blocks.attendees.message.group_register_failed', 'danger');
            }
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $p->redrawControl('programs');
        } else {
            $p->redirect('this');
        }
    }

    /**
     * Hromadně odhlásí program uživatelům.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function groupUnregister(array $ids): void
    {
        $p = $this->getPresenter();

        if (! $this->isAllowedModifyProgram($this->program)) {
            $p->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
        } else {
            try {
                $this->em->wrapInTransaction(function () use ($ids): void {
                    foreach ($ids as $id) {
                        $user = $this->userRepository->findById($id);
                        $this->commandBus->handle(new UnregisterProgram($user, $this->program));
                    }
                });
                $p->flashMessage('admin.program.blocks.attendees.message.group_unregister_success', 'success');
            } catch (HandlerFailedException) {
                $p->flashMessage('admin.program.blocks.attendees.message.group_unregister_failed', 'danger');
            }
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $p->redrawControl('programs');
        } else {
            $p->redirect('this');
        }
    }

    private function isAllowedModifyProgram(Program $program): bool
    {
        $user = $this->userRepository->findById($this->getPresenter()->getUser()->getId());

        return $user->isAllowedModifyBlock($program->getBlock());
    }
}
