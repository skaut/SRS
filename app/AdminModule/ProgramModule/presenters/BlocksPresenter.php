<?php

namespace App\AdminModule\ProgramModule\Presenters;


use App\AdminModule\ProgramModule\Components\IProgramBlocksGridControlFactory;
use App\AdminModule\ProgramModule\Forms\BlockFormFactory;
use App\Model\Program\BlockRepository;

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
     * @var BlockFormFactory
     * @inject
     */
    public $blockFormFactory;

    public function renderDefault() {
        $this->template->emptyUserInfo = $this->dbuser->getAbout() == '';
    }

    public function renderEdit($id) {
        $this->template->name = $this->blockRepository->findBlockById($id)->getName();
    }

    protected function createComponentProgramBlocksGrid($name)
    {
        return $this->programBlocksGridControlFactory->create($name);
    }

    protected function createComponentBlockForm($name)
    {
        return $this->blockFormFactory->create();
    }
}