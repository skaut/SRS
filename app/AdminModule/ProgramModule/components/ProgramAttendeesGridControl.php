<?php

namespace App\AdminModule\ProgramModule\Components;


use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\ResourceRepository;
use App\Model\Program\BlockRepository;
use App\Model\Program\CategoryRepository;
use App\Model\Program\Program;
use App\Model\Program\ProgramRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Ublaboo\DataGrid\DataGrid;

class ProgramAttendeesGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var Session */
    private $session;

    /** @var SessionSection */
    private $sessionSection;

    /** @var User */
    private $user;

    /** @var Program */
    private $program;

    public function __construct(Translator $translator, ProgramRepository $programRepository,
                                UserRepository $userRepository, Session $session)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->programRepository = $programRepository;
        $this->userRepository = $userRepository;

        $this->session = $session;
        $this->sessionSection = $session->getSection('srs');
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/program_attendees_grid.latte');
    }

    public function createComponentProgramAttendeesGrid($name)
    {
        $programId = $this->getPresenter()->getParameter('programId');
        if (!$programId)
            $programId = $this->sessionSection->programId;

        $this->program = $this->programRepository->findById($programId);

        $this->user = $this->userRepository->findById($this->getPresenter()->getUser()->getId());


        $grid = new DataGrid($this, $name);

        if (!$this->program) {
            $grid->setDataSource([]);
        } else {
            $grid->setTranslator($this->translator);

            $qb = $this->userRepository->createQueryBuilder('u')
                ->leftJoin('u.programs', 'p', 'WITH', 'p.id = :pid')
                ->innerJoin('u.roles', 'r')
                ->innerJoin('r.permissions', 'per')
                ->where('u.approved = true')
                ->andWhere('per.name = :permission')
                ->setParameter('pid', $programId)
                ->setParameter('permission', Permission::CHOOSE_PROGRAMS)
                ->orderBy('u.displayName');

            if ($this->program->getBlock()->getCategory()) {
                $qb = $qb
                    ->innerJoin('u.roles', 'rol')
                    ->innerJoin('rol.registerableCategories', 'c')
                    ->andWhere('c.id = :cid')
                    ->setParameter('cid', $this->program->getBlock()->getCategory()->getId());
            }

            $grid->setDataSource($qb);


            $grid->addGroupAction('admin.program.blocks_attendees_register')->onSelect[] = [$this, 'groupRegister'];
            $grid->addGroupAction('admin.program.blocks_attendees_unregister')->onSelect[] = [$this, 'groupUnregister'];

            $grid->addColumnText('displayName', 'admin.program.blocks_attendees_name');

            $grid->addColumnText('attends', 'admin.program.blocks_attendees_attends', 'pid')
                ->setRenderer(function ($item) {
                    return $item->getPrograms()->contains($this->program) ? 'Ano' : 'Ne';
                });


            $grid->addFilterSelect('attends', '', ['' => 'admin.common.all', 1 => 'admin.common.yes', 0 => 'admin.common.no'])
                ->setCondition(function ($qb, $value) {
                    if ($value === '')
                        return;
                    elseif ($value == 1)
                        $qb->innerJoin('u.programs', 'pro')
                            ->andWhere('pro.id = :proid')
                            ->setParameter('proid', $this->program->getId());
                    elseif ($value == 0)
                        $qb->leftJoin('u.programs', 'pro')
                            ->andWhere('(pro.id != :proid OR pro.id IS NULL)')
                            ->setParameter('proid', $this->program->getId());
                })
                ->setTranslateOptions();

            //$grid->setDefaultFilter(['attends' => 1], false); - zpusobuje problem, pokud neni zadny prihlaseny

            if ($this->user->isAllowed(Resource::USERS, Permission::MANAGE)) {
                $grid->addAction('detail', 'admin.common.detail', ':Admin:Users:detail')
                    ->setClass('btn btn-xs btn-primary')
                    ->addAttributes(['target' => '_blank']);
            }

            if ($this->user->isAllowedModifyBlock($this->program->getBlock())) {
                $grid->addAction('register', 'admin.program.blocks_attendees_register', 'register!')
                    ->setClass('btn btn-xs btn-success ajax');
                $grid->allowRowsAction('register', function ($item) {
                    return !$this->program->isAttendee($item);
                });

                $grid->addAction('unregister', 'admin.program.blocks_attendees_unregister', 'unregister!')
                    ->setClass('btn btn-xs btn-danger ajax');
                $grid->allowRowsAction('unregister', function ($item) {
                    return $this->program->isAttendee($item);
                });
            }
        }
    }

    public function handleRegister($id)
    {
        $editedUser = $this->userRepository->findById($id);

        $p = $this->getPresenter();

        if (!$this->user->isAllowedModifyBlock($this->program->getBlock()))
            $p->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
        elseif ($editedUser->hasProgramBlock($this->program->getBlock()))
            $p->flashMessage('admin.program.blocks_attendees_already_has_block', 'danger');
        else {
            $editedUser->addProgram($this->program);
            $this->userRepository->save($editedUser);

            $p->flashMessage('admin.program.blocks_attendees_registered', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $p->redrawControl('programs');
        }
        else {
            $this->redirect('this');
        }
    }

    public function handleUnregister($id)
    {
        $editedUser = $this->userRepository->findById($id);

        $p = $this->getPresenter();

        if (!$this->user->isAllowedModifyBlock($this->program->getBlock()))
            $p->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
        else {
            $editedUser->removeProgram($this->program);
            $this->userRepository->save($editedUser);

            $p->flashMessage('admin.program.blocks_attendees_unregistered', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $p->redrawControl('programs');
        }
        else {
            $this->redirect('this');
        }
    }

    public function groupRegister(array $ids) {
        foreach ($ids as $id) {
            $user = $this->userRepository->findById($id);
            if (!$user->hasProgramBlock($this->program->getBlock())) {
                $user->addProgram($this->program);
                $this->userRepository->save($user);
            }
        }

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.blocks_attendees_group_action_registered', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $p->redrawControl('programs');
        } else {
            $this->redirect('this');
        }
    }

    public function groupUnregister(array $ids) {
        foreach ($ids as $id) {
            $user = $this->userRepository->findById($id);
            $user->removeProgram($this->program);
            $this->userRepository->save($user);
        }

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.blocks_attendees_group_action_unregistered', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $p->redrawControl('programs');
        } else {
            $this->redirect('this');
        }
    }
}