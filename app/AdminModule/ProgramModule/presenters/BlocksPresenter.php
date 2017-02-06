<?php

namespace App\AdminModule\ProgramModule\Presenters;


use App\AdminModule\ProgramModule\Components\IProgramBlockScheduleGridControlFactory;
use App\AdminModule\ProgramModule\Components\IProgramBlocksGridControlFactory;
use App\AdminModule\ProgramModule\Forms\BlockFormFactory;
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
     * @var CategoryRepository
     * @inject
     */
    public $categoryRepository;

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
     * @var BlockFormFactory
     * @inject
     */
    public $blockFormFactory;

    public function renderDefault()
    {
        $this->template->emptyUserInfo = $this->dbuser->getAbout() == '';
    }

    public function renderDetail($id)
    {
        $block = $this->blockRepository->findBlockById($id);

        $this->template->block = $block;
        $this->template->basicBlockDuration = $this->settingsRepository->getValue('basic_block_duration');
    }

    public function renderEdit($id)
    {
        $block = $this->blockRepository->findBlockById($id);

        $this->template->block = $block;

        $this['blockForm']->setDefaults([
            'id' => $id,
            'name' => $block->getName(),
            'category' => $block->getCategory() ? $block->getCategory()->getId() : null,
            'lector' => $block->getLector() ? $block->getLector()->getId() : null,
            'duration' => $block->getDuration(),
            'capacity' => $block->getCapacity(),
            'mandatory' => $block->isMandatory(),
            'perex' => $block->getPerex(),
            'description' => $block->getDescription(),
            'tools' => $block->getTools()
        ]);
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
        $form = $this->blockFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($form['submitAndContinue']->isSubmittedBy()) {
                $block = $this->saveBlock($values);
                $this->redirect('Blocks:edit', ['id' => $block->getId()]);
            }
            else {
                $this->saveBlock($values);
                $this->redirect('Blocks:default');
            }
        };

        return $form;
    }

    private function saveBlock($values) {
        $id = $values['id'];

        $category = $values['category'] != '' ? $this->categoryRepository->findCategoryById($values['category']) : null;
        $lector = $values['lector'] != '' ? $this->userRepository->findUserById($values['lector']) : null;
        $capacity = $values['capacity'] != '' ? $values['capacity'] : null;

        if ($id == null) {
            $block = $this->blockRepository->addBlock($values['name'], $category, $lector, $values['duration'], $capacity, $values['mandatory'], $values['perex'], $values['description'], $values['tools']);
            $this->flashMessage('admin.program.blocks_added', 'success');
        }
        else {
            $block = $this->blockRepository->editBlock($values['id'], $values['name'], $category, $lector, $values['duration'], $capacity, $values['mandatory'], $values['perex'], $values['description'], $values['tools']);
            $this->flashMessage('admin.program.blocks_edited', 'success');
        }

        return $block;
    }
}