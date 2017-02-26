<?php

namespace App\AdminModule\ProgramModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\CategoryRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Nette;
use Nette\Application\UI\Form;

class BlockForm extends Nette\Object
{
    /** @var User */
    private $user;

    /** @var Block */
    private $block;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var BlockRepository */
    private $blockRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var CategoryRepository */
    private $categoryRepository;

    /** @var SettingsRepository */
    private $settingsRepository;

    public function __construct(BaseForm $baseFormFactory, BlockRepository $blockRepository,
                                UserRepository $userRepository, CategoryRepository $categoryRepository,
                                SettingsRepository $settingsRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->blockRepository = $blockRepository;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->settingsRepository = $settingsRepository;
    }

    public function create($id, $userId)
    {
        $this->block = $this->blockRepository->findById($id);
        $this->user = $this->userRepository->findById($userId);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addText('name', 'admin.program.blocks_name')
            ->addRule(Form::FILLED, 'admin.program.blocks_name_empty');

        $form->addSelect('category', 'admin.program.blocks_category', $this->categoryRepository->getCategoriesOptions())->setPrompt('');


        if ($this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_ALL_PROGRAMS))
            $lectorsOptions = $this->userRepository->getLectorsOptions();
        else
            $lectorsOptions = [$this->user->getId() => $this->user->getDisplayName()];

        $lectorColumn = $form->addSelect('lector', 'admin.program.blocks_lector', $lectorsOptions);

        if ($this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_ALL_PROGRAMS))
            $lectorColumn->setPrompt('');


        $form->addText('duration', 'admin.program.blocks_duration_form')
            ->addRule(Form::FILLED, 'admin.program.blocks_duration_empty')
            ->addRule(Form::NUMERIC, 'admin.program.blocks_duration_format');

        $form->addText('capacity', 'admin.program.blocks_capacity')
            ->setAttribute('data-toggle', 'tooltip')
            ->setAttribute('title', $form->getTranslator()->translate('admin.program.blocks_capacity_note'))
            ->addCondition(Form::FILLED)->addRule(Form::NUMERIC, 'admin.program.blocks_capacity_format');

        $form->addCheckbox('mandatory', 'admin.program.blocks_mandatory_form');

        $form->addTextArea('perex', 'admin.program.blocks_perex_form')
            ->addCondition(Form::FILLED)->addRule(Form::MAX_LENGTH, 'admin.program.blocks_perex_length', 160);

        $form->addTextArea('description', 'admin.program.blocks_description')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addText('tools', 'admin.program.blocks_tools');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setAttribute('class', 'btn btn-warning');

        if ($this->block) {
            $form['name']->addRule(Form::IS_NOT_IN, 'admin.program.blocks_name_exists', $this->blockRepository->findOthersNames($id));

            $form->setDefaults([
                'id' => $id,
                'name' => $this->block->getName(),
                'category' => $this->block->getCategory() ? $this->block->getCategory()->getId() : null,
                'lector' => $this->block->getLector() ? $this->block->getLector()->getId() : null,
                'duration' => $this->block->getDuration(),
                'capacity' => $this->block->getCapacity(),
                'mandatory' => $this->block->isMandatory(),
                'perex' => $this->block->getPerex(),
                'description' => $this->block->getDescription(),
                'tools' => $this->block->getTools()
            ]);
        }
        else {
            $form['name']->addRule(Form::IS_NOT_IN, 'admin.program.blocks_name_exists', $this->blockRepository->findAllNames());
        }

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
        if (!$form['cancel']->isSubmittedBy()) {
            if (!$this->block) {
                if (!$this->settingsRepository->getValue(Settings::IS_ALLOWED_ADD_BLOCK))
                    return;
                $this->block = new Block();
            } else if (!$this->user->isAllowedModifyBlock($this->block))
                return;

            $category = $values['category'] != '' ? $this->categoryRepository->findById($values['category']) : null;
            $lector = $values['lector'] != '' ? $this->userRepository->findById($values['lector']) : null;
            $capacity = $values['capacity'] !== '' ? $values['capacity'] : null;

            $this->block->setName($values['name']);
            $this->block->setCategory($category);
            $this->block->setLector($lector);
            $this->block->setDuration($values['duration']);
            $this->block->setCapacity($capacity);
            $this->block->setMandatory($values['mandatory']);
            $this->block->setPerex($values['perex']);
            $this->block->setDescription($values['description']);
            $this->block->setTools($values['tools']);

            $this->blockRepository->save($this->block);
        }
    }
}
