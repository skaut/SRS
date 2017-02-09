<?php

namespace App\AdminModule\ProgramModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\ACL\Role;
use App\Model\Program\CategoryRepository;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use Nette;
use Nette\Application\UI\Form;

class BlockForm extends Nette\Object
{
    /**
     * @var BaseForm
     */
    private $baseFormFactory;

    /**
     * @var SettingsRepository
     */
    private $settingsRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    public function __construct(BaseForm $baseFormFactory, SettingsRepository $settingsRepository, UserRepository $userRepository, CategoryRepository $categoryRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->settingsRepository = $settingsRepository;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addText('name', 'admin.program.blocks_name')
            ->addRule(Form::FILLED, 'admin.program.blocks_name_empty');

        $form->addSelect('category', 'admin.program.blocks_category', $this->categoryRepository->getCategoriesOptions())->setPrompt('');

        $form->addSelect('lector', 'admin.program.blocks_lector', $this->userRepository->getLectorsOptions())->setPrompt('');

        $form->addSelect('duration', 'admin.program.blocks_duration', $this->settingsRepository->getDurationsOptions())
            ->addRule(Form::FILLED, 'admin.program.blocks_duration_empty');

        $form->addText('capacity', 'admin.program.blocks_capacity')
            ->addCondition(Form::FILLED)->addRule(Form::NUMERIC, 'admin.program.blocks_capacity_format');

        $form->addCheckbox('mandatory', 'admin.program.blocks_mandatory_form');

        $form->addTextArea('perex', 'admin.program.blocks_perex_form')
            ->addCondition(Form::FILLED)->addRule(Form::MAX_LENGTH, 'admin.program.blocks_perex_length', 160);

        $form->addTextArea('description', 'admin.program.blocks_description')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addText('tools', 'admin.program.blocks_tools');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        return $form;
    }
}
