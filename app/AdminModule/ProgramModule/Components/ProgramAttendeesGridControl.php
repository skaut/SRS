<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;

use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use App\Model\Enums\ApplicationState;
use App\Model\Program\Program;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Queries\UserProgramBlocksQuery;
use App\Model\User\Queries\UserProgramsQuery;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Doctrine\ORM\QueryBuilder;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Localization\ITranslator;
use Throwable;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu účastníků programu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramAttendeesGridControl extends Control
{
    /**
     * Aktuální program.
     */
    private Program $program;

    private ITranslator $translator;

    private ProgramRepository $programRepository;

    private UserRepository $userRepository;

    private SessionSection $sessionSection;

    private CommandBus $commandBus;

    private QueryBus $queryBus;

    public function __construct(
        ITranslator $translator,
        ProgramRepository $programRepository,
        UserRepository $userRepository,
        Session $session,
        CommandBus $commandBus,
        QueryBus $queryBus
    ) {
        $this->translator        = $translator;
        $this->programRepository = $programRepository;
        $this->userRepository    = $userRepository;
        $this->commandBus        = $commandBus;
        $this->queryBus          = $queryBus;

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
            $this->program = $program;
            $user          = $this->userRepository->findById($this->getPresenter()->getUser()->getId());

            $grid->setTranslator($this->translator);

            $qb = $this->userRepository->createQueryBuilder('u') // todo: nahradit volanim repository
                ->leftJoin('u.programApplications', 'pa')
                ->join('pa.program', 'p', 'WITH', 'p.id = :pid')
                ->join('u.applications', 'a')
                ->join('a.subevents', 's')
                 ->andWhere('s.id = :sid')
                ->andWhere('a.validTo IS NULL')
                ->andWhere('(a.state = \'' . ApplicationState::PAID . '\' OR a.state = \'' . ApplicationState::PAID_FREE
                    . '\' OR a.state = \'' . ApplicationState::WAITING_FOR_PAYMENT . '\')')
                ->setParameter('pid', $program->getId())
                ->setParameter('sid', $program->getBlock()->getSubevent()->getId())
                ->orderBy('u.displayName');

            if ($this->program->getBlock()->getCategory()) {
                $qb = $qb
                    ->join('u.roles', 'rol')
                    ->join('rol.registerableCategories', 'c')
                    ->andWhere('c.id = :cid')
                    ->setParameter('cid', $this->program->getBlock()->getCategory()->getId());
            }

            $grid->setDataSource($qb);
            $grid->setItemsPerPageList([25, 50, 100, 250, 500]);
            $grid->setStrictSessionFilterValues(false);

            $grid->addGroupAction('admin.program.blocks_attendees_register')->onSelect[]   = [$this, 'groupRegister'];
            $grid->addGroupAction('admin.program.blocks_attendees_unregister')->onSelect[] = [$this, 'groupUnregister'];

            $grid->addColumnText('displayName', 'admin.program.blocks_attendees_name')
                ->setFilterText();

            $grid->addColumnText('attends', 'admin.program.blocks_attendees_attends', 'pid')
                ->setRenderer(function (User $item) {
                    $userPrograms = $this->queryBus->handle(new UserProgramsQuery($item));

                    return $userPrograms->contains($this->program)
                        ? $this->translator->translate('admin.common.yes')
                        : $this->translator->translate('admin.common.no');
                })
                ->setFilterSelect(['' => 'admin.common.all', 'yes' => 'admin.common.yes', 'no' => 'admin.common.no'])
                ->setCondition(static function (QueryBuilder $qb, string $value): void {
                    if ($value === '') {
                        return;
                    } elseif ($value === 'yes') {
                        $qb->andWhere('pa.alternate = false');
                    } elseif ($value === 'no') {
                        $qb->andWhere('pa.id = null');
                    }
                })
                ->setTranslateOptions();

            $grid->setDefaultFilter(['attends' => 'yes'], false);

            if ($user->isAllowed(SrsResource::USERS, Permission::MANAGE)) {
                $grid->addAction('detail', 'admin.common.detail', ':Admin:Users:detail')
                    ->setClass('btn btn-xs btn-primary')
                    ->addAttributes(['target' => '_blank']);
            }

            if ($user->isAllowedModifyBlock($this->program->getBlock())) {
                $grid->addAction('register', 'admin.program.blocks_attendees_register', 'register!')
                    ->setClass('btn btn-xs btn-success ajax');
                $grid->allowRowsAction('register', function ($item) {
                    $userPrograms = $this->queryBus->handle(new UserProgramsQuery($item));

                    return ! $userPrograms->contains($this->program);
                });

                $grid->addAction('unregister', 'admin.program.blocks_attendees_unregister', 'unregister!')
                    ->setClass('btn btn-xs btn-danger ajax');
                $grid->allowRowsAction('unregister', function ($item) {
                    $userPrograms = $this->queryBus->handle(new UserProgramsQuery($item));

                    return $userPrograms->contains($this->program);
                });
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
        } elseif ($this->queryBus->handle(new UserProgramBlocksQuery($user))->contains($program->getBlock())) {
            $p->flashMessage('admin.program.blocks_attendees_already_has_block', 'danger');
        } else {
            $this->commandBus->handle(new RegisterProgram($user, $program));
            $p->flashMessage('admin.program.blocks_attendees_registered', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $p->redrawControl('programs');
        } else {
            $this->redirect('this');
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
            $this->commandBus->handle(new UnregisterProgram($user, $program));
            $p->flashMessage('admin.program.blocks_attendees_unregistered', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $p->redrawControl('programs');
        } else {
            $this->redirect('this');
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
            foreach ($ids as $id) {
                $user = $this->userRepository->findById($id);
                if (! $this->queryBus->handle(new UserProgramBlocksQuery($user))->contains($this->program->getBlock())) {
                    $this->commandBus->handle(new RegisterProgram($user, $this->program));
                }
            }

            $p->flashMessage('admin.program.blocks_attendees_group_action_registered', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $p->redrawControl('programs');
        } else {
            $this->redirect('this');
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
            foreach ($ids as $id) {
                $user = $this->userRepository->findById($id);
                if ($this->queryBus->handle(new UserProgramBlocksQuery($user))->contains($this->program->getBlock())) {
                    $this->commandBus->handle(new UnregisterProgram($user, $this->program));
                }
            }

            $p->flashMessage('admin.program.blocks_attendees_group_action_unregistered', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $p->redrawControl('programs');
        } else {
            $this->redirect('this');
        }
    }

    private function isAllowedModifyProgram(Program $program): bool
    {
        $user = $this->userRepository->findById($this->getPresenter()->getUser()->getId());

        return $user->isAllowedModifyBlock($program->getBlock());
    }
}
