<?php

namespace App\AdminModule\ProgramModule\Presenters;

use App\AdminModule\ProgramModule\Components\IProgramAttendeesGridControlFactory;
use App\AdminModule\ProgramModule\Components\IProgramBlocksGridControlFactory;
use App\AdminModule\ProgramModule\Forms\BlockForm;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Program\BlockRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use Nette\Application\UI\Form;
use Nette\Http\Session;


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


    public function renderDefault()
    {
        $this->template->emptyUserInfo = empty($this->dbuser->getAbout());

        $this->session->getSection('srs')->programId = 0;
    }

    public function renderDetail($id)
    {
        $block = $this->blockRepository->findById($id);

        $this->template->block = $block;
        $this->template->programId = $this->session->getSection('srs')->programId;
        $this->template->userAllowedModifySchedule = $this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE) &&
            $this->settingsRepository->getValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE);
    }

    public function renderEdit($id)
    {
        $block = $this->blockRepository->findById($id);

        if (!$this->userRepository->findById($this->getUser()->getId())->isAllowedModifyBlock($block)) {
            $this->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
            $this->redirect('Blocks:default');
        }

        $this->template->block = $block;
    }

    public function handleShowAttendees($programId)
    {
        $this->session->getSection('srs')->programId = $programId;

        $this->template->programId = $programId;

        if ($this->isAjax())
            $this->redrawControl('programs');
        else
            $this->redirect('this');
    }

    public function handleDeleteProgram($programId)
    {
        $program = $this->programRepository->findById($programId);

        if (!$this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_SCHEDULE) ||
            !$this->settingsRepository->getValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE)
        )
            $this->getPresenter()->flashMessage('admin.program.blocks_program_modify_schedule_not_allowed', 'danger');
        else {
            $this->programRepository->remove($program);
            $this->getPresenter()->flashMessage('admin.program.blocks_program_deleted', 'success');
        }

        $this->redirect('this');
    }

    protected function createComponentProgramBlocksGrid()
    {
        return $this->programBlocksGridControlFactory->create();
    }

    protected function createComponentProgramAttendeesGrid()
    {
        return $this->programAttendeesGridControlFactory->create();
    }

    protected function createComponentBlockForm()
    {
        $form = $this->blockFormFactory->create($this->getParameter('id'), $this->getUser()->getId());

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($form['cancel']->isSubmittedBy())
                $this->redirect('Blocks:default');

            if (!$values['id']) {
                if (!$this->settingsRepository->getValue(Settings::IS_ALLOWED_ADD_BLOCK)) {
                    $this->flashMessage('admin.program.blocks_add_not_allowed', 'danger');
                    $this->redirect('Blocks:default');
                }
            } else {
                $user = $this->userRepository->findById($this->user->getId());
                $block = $this->blockRepository->findById($values['id']);

                if ($values['id'] && !$user->isAllowedModifyBlock($block)) {
                    $this->flashMessage('admin.program.blocks_edit_not_allowed', 'danger');
                    $this->redirect('Blocks:default');
                }
            }

            $this->flashMessage('admin.program.blocks_saved', 'success');

            if ($form['submitAndContinue']->isSubmittedBy()) {
                $id = $values['id'] ?: $this->blockRepository->findLastId();
                $this->redirect('Blocks:edit', ['id' => $id]);
            } else
                $this->redirect('Blocks:default');
        };

        return $form;
    }
}