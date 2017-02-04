<?php

namespace App\AdminModule\ProgramModule\Presenters;


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
     * @var BlockFormFactory
     * @inject
     */
    public $blockFormFactory;

    public function renderDefault()
    {
        $this->template->emptyUserInfo = $this->dbuser->getAbout() == '';
    }

    public function renderEdit($id)
    {
        $this->template->name = $this->blockRepository->findBlockById($id)->getName();
        $this->template->id = $id;
    }

    protected function createComponentProgramBlocksGrid($name)
    {
        return $this->programBlocksGridControlFactory->create($name);
    }

    protected function createComponentBlockForm($name, $id = null)
    {
        $form = $this->blockFormFactory->create();

        if ($id !== null) {
            $block = $this->blockRepository->findBlockById($id);
            $form->setDefaults([
                'id' => $id,
                'name' => $block->getName(),
                'category' => $block->getCategory()->getId(),
                'lector' => $block->getLector()->getId(),
                'duration' => $block->getDuration(),
                'capacity' => $block->getCapacity(),
                'mandatory' => $block->isMandatory(),
                'perex' => $block->getPerex(),
                'description' => $block->getDescription(),
                'tools' => $block->getTools()
            ]);
        }

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($form['submitAndContinue']->isSubmittedBy()) {
                $this->saveBlock($values);
            }
            else {
                $this->saveBlock($values);
                $this->redirect('Blocks:default');
            }
        };

        return $form;
    }

    private function saveBlock($values)
    {
        $id = $values['id'];

        $category = $values['category'] == '' ? null : $this->categoryRepository->findCategoryById($values['category']);
        $lector = $values['lector'] == '' ? null : $this->userRepository->findUserById($values['lector']);

        if ($id != '')
            $block = $this->blockRepository->editBlock($id, $values['name'], $category, $lector, $values['duration'], $values['capacity'], $values['mandatory'], $values['perex'], $values['description'], $values['tools']);
        else
            $block = $this->blockRepository->addBlock($values['name'], $category, $lector, $values['duration'], $values['capacity'], $values['mandatory'], $values['perex'], $values['description'], $values['tools']);

        if ($id != '')
            $this->flashMessage('admin.program.blocks_edited', 'success');
        else
            $this->flashMessage('admin.program.blocks_added', 'success');
    }
}