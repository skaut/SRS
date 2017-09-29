<?php

namespace App\AdminModule\ProgramModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\CategoryRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro úpravu programového bloku.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BlockForm extends Nette\Object
{
    /**
     * Přihlášený uživatel.
     * @var User
     */
    private $user;

    /**
     * Upravovaný programový blok.
     * @var Block
     */
    private $block;

    /**
     * Jsou vytvořené podakce.
     * @var int
     */
    private $subeventsExists;

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

    /** @var ProgramRepository */
    private $programRepository;

    /** @var SubeventRepository */
    private $subeventRepository;


    /**
     * BlockForm constructor.
     * @param BaseForm $baseFormFactory
     * @param BlockRepository $blockRepository
     * @param UserRepository $userRepository
     * @param CategoryRepository $categoryRepository
     * @param SettingsRepository $settingsRepository
     * @param ProgramRepository $programRepository
     * @param SubeventRepository $subeventRepository
     */
    public function __construct(BaseForm $baseFormFactory, BlockRepository $blockRepository,
                                UserRepository $userRepository, CategoryRepository $categoryRepository,
                                SettingsRepository $settingsRepository, ProgramRepository $programRepository,
                                SubeventRepository $subeventRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->blockRepository = $blockRepository;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->settingsRepository = $settingsRepository;
        $this->programRepository = $programRepository;
        $this->subeventRepository = $subeventRepository;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @param $userId
     * @return Form
     */
    public function create($id, $userId)
    {
        $this->block = $this->blockRepository->findById($id);
        $this->user = $this->userRepository->findById($userId);

        $this->subeventsExists = $this->subeventRepository->explicitSubeventsExists();

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addText('name', 'admin.program.blocks_name')
            ->addRule(Form::FILLED, 'admin.program.blocks_name_empty');

        if ($this->subeventsExists) {
            $form->addSelect('subevent', 'admin.program.blocks_subevent', $this->subeventRepository->getSubeventsOptions())
                ->setPrompt('')
                ->addRule(Form::FILLED, 'admin.program.blocks_subevent_empty');
        }

        $form->addSelect('category', 'admin.program.blocks_category', $this->categoryRepository->getCategoriesOptions())
            ->setPrompt('');


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

        $form->addCheckbox('mandatory', 'admin.program.blocks_mandatory_form')
            ->addCondition(Form::EQUAL, TRUE)
            ->toggle('autoRegisterCheckbox');

        $form->addCheckbox('autoRegister', 'admin.program.blocks_auto_register')
            ->setOption('id', 'autoRegisterCheckbox')
            ->setAttribute('data-toggle', 'tooltip')
            ->setAttribute('title', $form->getTranslator()->translate('admin.program.blocks_auto_register_note'))
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateAutoRegister'], 'admin.program.blocks_auto_register_not_allowed');

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
                'category' => $this->block->getCategory() ? $this->block->getCategory()->getId() : NULL,
                'lector' => $this->block->getLector() ? $this->block->getLector()->getId() : NULL,
                'duration' => $this->block->getDuration(),
                'capacity' => $this->block->getCapacity(),
                'mandatory' => $this->block->getMandatory() > 0,
                'autoRegister' => $this->block->getMandatory() == 2,
                'perex' => $this->block->getPerex(),
                'description' => $this->block->getDescription(),
                'tools' => $this->block->getTools()
            ]);

            if ($this->subeventsExists) {
                $form->setDefaults([
                    'subevent' => $this->block->getSubevent() ? $this->block->getSubevent()->getId() : NULL
                ]);
            }
        } else {
            $form['name']->addRule(Form::IS_NOT_IN, 'admin.program.blocks_name_exists', $this->blockRepository->findAllNames());
        }

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     */
    public function processForm(Form $form, \stdClass $values)
    {
        if (!$form['cancel']->isSubmittedBy()) {
            if (!$this->block) {
                if (!$this->settingsRepository->getValue(Settings::IS_ALLOWED_ADD_BLOCK))
                    return;
                $this->block = new Block();
            } else if (!$this->user->isAllowedModifyBlock($this->block))
                return;

            $oldMandatory = $this->block->getMandatory();
            $oldCategory = $this->block->getCategory();
            $oldSubevent = $this->block->getSubevent();

            $category = $values['category'] != '' ? $this->categoryRepository->findById($values['category']) : NULL;
            $lector = $values['lector'] != '' ? $this->userRepository->findById($values['lector']) : NULL;
            $capacity = $values['capacity'] !== '' ? $values['capacity'] : NULL;

            $this->block->setName($values['name']);
            $this->block->setCategory($category);
            $this->block->setLector($lector);
            $this->block->setDuration($values['duration']);
            $this->block->setCapacity($capacity);
            $this->block->setMandatory($values['mandatory'] ? ((array_key_exists('autoRegister', $values) && $values['autoRegister']) ? 2 : 1) : 0);
            $this->block->setPerex($values['perex']);
            $this->block->setDescription($values['description']);
            $this->block->setTools($values['tools']);

            if ($this->subeventsExists) {
                $subevent = $values['subevent'] != '' ? $this->subeventRepository->findById($values['subevent']) : NULL;
                $this->block->setSubevent($subevent);
            }
            else {
                $this->block->setSubevent($this->subeventRepository->findImplicit());
            }

            $this->blockRepository->save($this->block);

            //odstraneni ucastniku, pokud se odstrani automaticke prihlasovani
            if ($oldMandatory == 2 && $this->block->getMandatory() != 2) {
                foreach ($this->block->getPrograms() as $program) {
                    $program->removeAllAttendees();
                }
            }

            //pridani ucastniku, pokud je pridano automaticke prihlaseni
            if ($oldMandatory != 2 && $this->block->getMandatory() == 2) {
                foreach ($this->block->getPrograms() as $program) {
                    $program->setAttendees($this->userRepository->findProgramAllowed($program));
                }
            }

            //aktualizace ucastniku pri zmene kategorie nebo podakce
            if ($oldMandatory == $this->block->getMandatory() && (
                $this->block->getCategory() !== $oldCategory) || ($this->block->getSubevent() !== $oldSubevent)
            ) {
                $this->programRepository->updateUsersPrograms($this->userRepository->findAll());
            }

            $this->blockRepository->save($this->block);
        }
    }

    /**
     * Ověří, zda může být program automaticky přihlašovaný.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateAutoRegister($field, $args)
    {
        if ($this->block) {
            if ($this->block->getMandatory() != 2 && ($this->block->getProgramsCount() > 1 ||
                    ($this->block->getProgramsCount() == 1 && $this->programRepository->hasOverlappingProgram(
                            $this->block->getPrograms()->first(),
                            $this->block->getPrograms()->first()->getStart(),
                            $this->block->getPrograms()->first()->getEnd())
                    )
                )
            )
                return FALSE;
        }
        return TRUE;
    }
}
