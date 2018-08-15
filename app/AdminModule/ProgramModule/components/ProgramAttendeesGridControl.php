<?php
declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Enums\ApplicationState;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Program\Program;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\MailService;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu účastníků programu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramAttendeesGridControl extends Control
{
    /**
     * Aktuální program.
     * @var Program
     */
    private $program;

    /**
     * Přihlášený uživatel.
     * @var User
     */
    private $user;

    /** @var Translator */
    private $translator;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var MailService */
    private $mailService;

    /** @var Session */
    private $session;

    /** @var SessionSection */
    private $sessionSection;


    /**
     * ProgramAttendeesGridControl constructor.
     * @param Translator $translator
     * @param ProgramRepository $programRepository
     * @param UserRepository $userRepository
     * @param SettingsRepository $settingsRepository
     * @param MailService $mailService
     * @param Session $session
     */
    public function __construct(Translator $translator, ProgramRepository $programRepository,
                                UserRepository $userRepository, SettingsRepository $settingsRepository,
                                MailService $mailService, Session $session)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->programRepository = $programRepository;
        $this->userRepository = $userRepository;
        $this->settingsRepository = $settingsRepository;
        $this->mailService = $mailService;

        $this->session = $session;
        $this->sessionSection = $session->getSection('srs');
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/program_attendees_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentProgramAttendeesGrid($name)
    {
        $programId = $this->getPresenter()->getParameter('programId');
        if (!$programId) {
            $programId = $this->sessionSection->programId;
        }

        $program = $this->programRepository->findById($programId);


        $grid = new DataGrid($this, $name);

        if (!$program) {
            $grid->setDataSource([]);
        } else {
            $this->program = $program;
            $this->user = $this->userRepository->findById($this->getPresenter()->getUser()->getId());

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


            $grid->addGroupAction('admin.program.blocks_attendees_register')->onSelect[] = [$this, 'groupRegister'];
            $grid->addGroupAction('admin.program.blocks_attendees_unregister')->onSelect[] = [$this, 'groupUnregister'];

            $grid->addColumnText('displayName', 'admin.program.blocks_attendees_name')
                ->setFilterText();
			
            $grid->addColumnText('attends', 'admin.program.blocks_attendees_attends', 'pid')
                ->setRenderer(function ($item) {
                    return $item->getPrograms()->contains($this->program)
                        ? $this->translator->translate('admin.common.yes')
                        : $this->translator->translate('admin.common.no');
                })
                ->setFilterSelect(['' => 'admin.common.all', 1 => 'admin.common.yes', 0 => 'admin.common.no'])
                ->setCondition(function ($qb, $value) {
                    if ($value === '') {
                        return;
                    }
                    elseif ($value == 1) {
                        $qb->innerJoin('u.programs', 'pro')
                            ->andWhere('pro.id = :proid')
                            ->setParameter('proid', $this->program->getId());
                    }
                    elseif ($value == 0) {
                        $qb->leftJoin('u.programs', 'pro')
                            ->andWhere('(pro.id != :proid OR pro.id IS NULL)')
                            ->setParameter('proid', $this->program->getId());
                    }
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

    /**
     * Přihlásí uživatele na program.
     * @param $id
     * @throws \App\Model\Settings\SettingsException
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     * @throws \Ublaboo\Mailing\Exception\MailingException
     * @throws \Ublaboo\Mailing\Exception\MailingMailCreationException
     */
    public function handleRegister($id)
    {
        $editedUser = $this->userRepository->findById($id);

        $p = $this->getPresenter();

        $user = $this->userRepository->findById($this->getPresenter()->getUser()->getId());
        $program = $this->programRepository->findById($this->sessionSection->programId);

        if (!$user->isAllowedModifyBlock($program->getBlock())) {
            $p->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
        }
        elseif ($editedUser->hasProgramBlock($program->getBlock())) {
            $p->flashMessage('admin.program.blocks_attendees_already_has_block', 'danger');
        }
        else {
            $editedUser->addProgram($program);
            $this->userRepository->save($editedUser);

            $this->mailService->sendMailFromTemplate($editedUser, '', Template::PROGRAM_REGISTERED, [
                TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
                TemplateVariable::PROGRAM_NAME => $program->getBlock()->getName()
            ]);

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
     * @param $id
     * @throws \App\Model\Settings\SettingsException
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     * @throws \Ublaboo\Mailing\Exception\MailingException
     * @throws \Ublaboo\Mailing\Exception\MailingMailCreationException
     */
    public function handleUnregister($id)
    {
        $editedUser = $this->userRepository->findById($id);

        $p = $this->getPresenter();

        $user = $this->userRepository->findById($this->getPresenter()->getUser()->getId());
        $program = $this->programRepository->findById($this->sessionSection->programId);

        if (!$user->isAllowedModifyBlock($program->getBlock()))
            $p->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
        else {
            $editedUser->removeProgram($program);
            $this->userRepository->save($editedUser);

            $this->mailService->sendMailFromTemplate($editedUser, '', Template::PROGRAM_UNREGISTERED, [
                TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
                TemplateVariable::PROGRAM_NAME => $program->getBlock()->getName()
            ]);

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
     * @param array $ids
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Nette\Application\AbortException
     */
    public function groupRegister(array $ids)
    {
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

    /**
     * Hromadně odhlásí program uživatelům.
     * @param array $ids
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Nette\Application\AbortException
     */
    public function groupUnregister(array $ids)
    {
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
