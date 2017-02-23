<?php

namespace App\AdminModule\ProgramModule\Presenters;


use App\AdminModule\ProgramModule\Components\IProgramAttendeesGridControlFactory;
use App\AdminModule\ProgramModule\Components\IProgramBlockScheduleGridControlFactory;
use App\AdminModule\ProgramModule\Components\IProgramBlocksGridControlFactory;
use App\AdminModule\ProgramModule\Forms\BlockForm;
use App\AdminModule\ProgramModule\Forms\BlockFormFactory;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\CategoryRepository;
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
    }

    public function handleShowAttendees($programId) {
        $this->session->getSection('srs')->programId = $programId;

        $this->template->programId = $programId;

        if ($this->isAjax())
            $this->redrawControl('programs');
        else
            $this->redirect('this');
    }

    public function handleDelete($programId) {
        //TODO
    }

    public function renderEdit($id)
    {
        $block = $this->blockRepository->findById($id);

        $this->template->block = $block;
    }

    protected function createComponentProgramBlocksGrid($name)
    {
        return $this->programBlocksGridControlFactory->create($name);
    }

    protected function createComponentProgramAttendeesGrid($name)
    {
        return $this->programAttendeesGridControlFactory->create($name);
    }

    protected function createComponentBlockForm($name)
    {
        $form = $this->blockFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.program.blocks_saved', 'success');

            if ($form['submitAndContinue']->isSubmittedBy()) {
                $id = $values['id'] ?: $this->blockRepository->findLastId();
                $this->redirect('Blocks:edit', ['id' => $id]);
            }
            else
                $this->redirect('Blocks:default');
        };

        return $form;
    }
}