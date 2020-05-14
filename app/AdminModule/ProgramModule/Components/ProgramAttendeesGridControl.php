<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;

use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use App\Model\Enums\ApplicationState;
use App\Model\Program\Program;
use App\Model\Program\ProgramRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ProgramService;
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

    /**
     * Přihlášený uživatel.
     */
    private User $user;

    private ITranslator $translator;

    private ProgramRepository $programRepository;

    private UserRepository $userRepository;

    private ProgramService $programService;

    private SessionSection $sessionSection;

    public function __construct(
        ITranslator $translator,
        ProgramRepository $programRepository,
        UserRepository $userRepository,
        ProgramService $programService,
        Session $session
    ) {
        $this->translator        = $translator;
        $this->programRepository = $programRepository;
        $this->userRepository    = $userRepository;
        $this->programService    = $programService;

        $this->sessionSection = $session->getSection('srs');
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/program_attendees_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridException
     */
    public function createComponentProgramAttendeesGrid(string $name) : void
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
            $this->user    = $this->userRepository->findById($this->getPresenter()->getUser()->getId());

            $grid->setTranslator($this->translator);

            $qb = $this->userRepository->createQueryBuilder('u')
                ->leftJoin('u.programs', 'p', 'WITH', 'p.id = :pid')
                ->innerJoin('u.roles', 'r')
                ->innerJoin('r.permissions', 'per')
                ->innerJoin('u.applications', 'a')
                ->innerJoin('a.subevents', 's')
                ->where('per.name = :permission')
                ->andWhere('s.id = :sid')
                ->andWhere('a.validTo IS NULL')
                ->andWhere('(a.state = \'' . ApplicationState::PAID . '\' OR a.state = \'' . ApplicationState::PAID_FREE
                    . '\' OR a.state = \'' . ApplicationState::WAITING_FOR_PAYMENT . '\')')
                ->setParameter('pid', $program->getId())
                ->setParameter('permission', Permission::CHOOSE_PROGRAMS)
                ->setParameter('sid', $program->getBlock()->getSubevent()->getId())
                ->orderBy('u.displayName');

            if ($this->program->getBlock()->getCategory()) {
                $qb = $qb
                    ->innerJoin('u.roles', 'rol')
                    ->innerJoin('rol.registerableCategories', 'c')
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
                    return $item->getPrograms()->contains($this->program)
                        ? $this->translator->translate('admin.common.yes')
                        : $this->translator->translate('admin.common.no');
                })
                ->setFilterSelect(['' => 'admin.common.all', 'yes' => 'admin.common.yes', 'no' => 'admin.common.no'])
                ->setCondition(function (QueryBuilder $qb, string $value) : void {
                    if ($value === '') {
                        return;
                    } elseif ($value === 'yes') {
                        $qb->innerJoin('u.programs', 'pro')
                            ->andWhere('pro.id = :proid')
                            ->setParameter('proid', $this->program->getId());
                    } elseif ($value === 'no') {
                        $qb->leftJoin('u.programs', 'pro')
                            ->andWhere('u not in (:attendees)')
                            ->setParameter('attendees', $this->program->getAttendees());
                    }
                })
                ->setTranslateOptions();

            $grid->setDefaultFilter(['attends' => 'yes'], false);

            if ($this->user->isAllowed(SrsResource::USERS, Permission::MANAGE)) {
                $grid->addAction('detail', 'admin.common.detail', ':Admin:Users:detail')
                    ->setClass('btn btn-xs btn-primary')
                    ->addAttributes(['target' => '_blank']);
            }

            if ($this->user->isAllowedModifyBlock($this->program->getBlock())) {
                $grid->addAction('register', 'admin.program.blocks_attendees_register', 'register!')
                    ->setClass('btn btn-xs btn-success ajax');
                $grid->allowRowsAction('register', function ($item) {
                    return ! $this->program->isAttendee($item);
                });

                $grid->addAction('unregister', 'admin.program.blocks_attendees_unregister', 'unregister!')
                    ->setClass('btn btn-xs btn-danger ajax');
                $grid->allowRowsAction('unregister', function ($item) {
                    return $this->program->isAttendee($item);
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
    public function handleRegister(int $id) : void
    {
        $user = $this->userRepository->findById($id);

        $p = $this->getPresenter();

        $program = $this->programRepository->findById($this->sessionSection->programId);

        if (! $this->isAllowedModifyProgram($program)) {
            $p->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
        } elseif ($user->hasProgramBlock($program->getBlock())) {
            $p->flashMessage('admin.program.blocks_attendees_already_has_block', 'danger');
        } else {
            $this->programService->registerProgram($user, $program, true);
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
    public function handleUnregister(int $id) : void
    {
        $user = $this->userRepository->findById($id);

        $p = $this->getPresenter();

        $program = $this->programRepository->findById($this->sessionSection->programId);

        if (! $this->isAllowedModifyProgram($program)) {
            $p->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
        } else {
            $this->programService->unregisterProgram($user, $program, true);
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
    public function groupRegister(array $ids) : void
    {
        $p = $this->getPresenter();

        if (! $this->isAllowedModifyProgram($this->program)) {
            $p->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
        } else {
            foreach ($ids as $id) {
                $user = $this->userRepository->findById($id);
                if (! $user->hasProgramBlock($this->program->getBlock())) {
                    $this->programService->registerProgram($user, $this->program, true);
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
    public function groupUnregister(array $ids) : void
    {
        $p = $this->getPresenter();

        if (! $this->isAllowedModifyProgram($this->program)) {
            $p->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
        } else {
            foreach ($ids as $id) {
                $user = $this->userRepository->findById($id);
                if ($user->hasProgramBlock($this->program->getBlock())) {
                    $this->programService->unregisterProgram($user, $this->program, true);
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

    private function isAllowedModifyProgram(Program $program) : bool
    {
        $user = $this->userRepository->findById($this->getPresenter()->getUser()->getId());

        return $user->isAllowedModifyBlock($program->getBlock());
    }
}
