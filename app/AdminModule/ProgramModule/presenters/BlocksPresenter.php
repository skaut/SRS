<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Presenters;

use App\AdminModule\ProgramModule\Components\IProgramAttendeesGridControlFactory;
use App\AdminModule\ProgramModule\Components\IProgramBlocksGridControlFactory;
use App\AdminModule\ProgramModule\Components\ProgramAttendeesGridControl;
use App\AdminModule\ProgramModule\Components\ProgramBlocksGridControl;
use App\AdminModule\ProgramModule\Forms\BlockForm;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Program\BlockRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Services\ProgramService;
use Doctrine\ORM\NonUniqueResultException;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Http\Session;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující správu programových bloků.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BlocksPresenter extends ProgramBasePresenter
{
    /**
     * @var BlockRepository
     * @inject
     */
    public $blockRepository;

    /**
     * @var ProgramRepository
     * @inject
     */
    public $programRepository;

    /**
     * @var IProgramBlocksGridControlFactory
     * @inject
     */
    public $programBlocksGridControlFactory;

    /**
     * @var IProgramAttendeesGridControlFactory
     * @inject
     */
    public $programAttendeesGridControlFactory;

    /**
     * @var BlockForm
     * @inject
     */
    public $blockFormFactory;

    /**
     * @var Session
     * @inject
     */
    public $session;

    /**
     * @var ProgramService
     * @inject
     */
    public $programService;


    public function renderDefault() : void
    {
        $this->template->emptyUserInfo = empty($this->dbuser->getAbout());

        $this->session->getSection('srs')->programId = 0;
    }

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    public function renderDetail(int $id) : void
    {
        $block = $this->blockRepository->findById($id);

        $this->template->block                     = $block;
        $this->template->programId                 = $this->session->getSection('srs')->programId;
        $this->template->userAllowedModifySchedule = $this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE) &&
            $this->settingsService->getBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE);
    }

    /**
     * @throws AbortException
     */
    public function renderEdit(int $id) : void
    {
        $block = $this->blockRepository->findById($id);

        if (! $this->userRepository->findById($this->getUser()->getId())->isAllowedModifyBlock($block)) {
            $this->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
            $this->redirect('Blocks:default');
        }

        $this->template->block = $block;
    }

    /**
     * Zobrazí přehled účastníků u vybraného programu.
     * @throws AbortException
     */
    public function handleShowAttendees(int $programId) : void
    {
        $this->session->getSection('srs')->programId = $programId;

        $this->template->programId = $programId;

        if ($this->isAjax()) {
            $this->redrawControl('programs');
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Odstraní vybraný program.
     * @throws SettingsException
     * @throws AbortException
     * @throws Throwable
     */
    public function handleDeleteProgram(int $programId) : void
    {
        $program = $this->programRepository->findById($programId);

        if (! $this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE) ||
            ! $this->settingsService->getBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE)
        ) {
            $this->getPresenter()->flashMessage('admin.program.blocks_program_modify_schedule_not_allowed', 'danger');
        } else {
            $this->programService->removeProgram($program);
            $this->getPresenter()->flashMessage('admin.program.blocks_program_deleted', 'success');
        }

        $this->redirect('this');
    }

    protected function createComponentProgramBlocksGrid() : ProgramBlocksGridControl
    {
        return $this->programBlocksGridControlFactory->create();
    }

    protected function createComponentProgramAttendeesGrid() : ProgramAttendeesGridControl
    {
        return $this->programAttendeesGridControlFactory->create();
    }

    /**
     * @throws NonUniqueResultException
     */
    protected function createComponentBlockForm() : Form
    {
        $form = $this->blockFormFactory->create((int) $this->getParameter('id'), $this->getUser()->getId());

        $form->onSuccess[] = function (Form $form, stdClass $values) : void {
            if ($form['cancel']->isSubmittedBy()) {
                $this->redirect('Blocks:default');
            }

            if (! $values->id) {
                if (! $this->settingsService->getBoolValue(Settings::IS_ALLOWED_ADD_BLOCK)) {
                    $this->flashMessage('admin.program.blocks_add_not_allowed', 'danger');
                    $this->redirect('Blocks:default');
                }
            } else {
                $user  = $this->userRepository->findById($this->user->getId());
                $block = $this->blockRepository->findById((int) $values->id);

                if (! $user->isAllowedModifyBlock($block)) {
                    $this->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
                    $this->redirect('Blocks:default');
                }
            }

            $this->flashMessage('admin.program.blocks_saved', 'success');

            if ($form['submitAndContinue']->isSubmittedBy()) {
                $id = $values->id ?: $this->blockRepository->findLastId();
                $this->redirect('Blocks:edit', ['id' => $id]);
            } else {
                $this->redirect('Blocks:default');
            }
        };

        return $form;
    }
}
