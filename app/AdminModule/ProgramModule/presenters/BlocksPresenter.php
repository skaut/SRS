<?php

namespace App\AdminModule\ProgramModule\Presenters;


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
     * @var IProgramBlockScheduleGridControlFactory
     * @inject
     */
    public $programBlockScheduleGridControlFactory;

    /**
     * @var BlockForm
     * @inject
     */
    public $blockFormFactory;


    public function renderDefault()
    {
        $this->template->emptyUserInfo = empty($this->dbuser->getAbout());
    }

    public function renderDetail($id)
    {
        $block = $this->blockRepository->findById($id);

        $this->template->block = $block;
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

    protected function createComponentProgramBlockScheduleGrid($name)
    {
        return $this->programBlockScheduleGridControlFactory->create($name);
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