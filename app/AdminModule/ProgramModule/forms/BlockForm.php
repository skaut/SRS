<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\CategoryRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ProgramService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Nette;
use Nette\Application\UI\Form;
use function array_key_exists;

/**
 * Formulář pro úpravu programového bloku.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BlockForm
{
    use Nette\SmartObject;

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

    /** @var ProgramService */
    private $programService;


    public function __construct(
        BaseForm $baseFormFactory,
        BlockRepository $blockRepository,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        SettingsRepository $settingsRepository,
        ProgramRepository $programRepository,
        SubeventRepository $subeventRepository,
        ProgramService $programService
    ) {
        $this->baseFormFactory    = $baseFormFactory;
        $this->blockRepository    = $blockRepository;
        $this->userRepository     = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->settingsRepository = $settingsRepository;
        $this->programRepository  = $programRepository;
        $this->subeventRepository = $subeventRepository;
        $this->programService     = $programService;
    }

    /**
     * Vytvoří formulář.
     * @throws NonUniqueResultException
     */
    public function create(int $id, int $userId) : Form
    {
        $this->block = $this->blockRepository->findById($id);
        $this->user  = $this->userRepository->findById($userId);

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

        if ($this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_ALL_PROGRAMS)) {
            $lectorsOptions = $this->userRepository->getLectorsOptions();
        } else {
            $lectorsOptions = [$this->user->getId() => $this->user->getDisplayName()];
        }

        $lectorColumn = $form->addSelect('lector', 'admin.program.blocks_lector', $lectorsOptions);

        if ($this->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_ALL_PROGRAMS)) {
            $lectorColumn->setPrompt('');
        }

        $form->addText('duration', 'admin.program.blocks_duration_form')
            ->addRule(Form::FILLED, 'admin.program.blocks_duration_empty')
            ->addRule(Form::NUMERIC, 'admin.program.blocks_duration_format');

        $form->addText('capacity', 'admin.program.blocks_capacity')
            ->setAttribute('data-toggle', 'tooltip')
            ->setAttribute('title', $form->getTranslator()->translate('admin.program.blocks_capacity_note'))
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.program.blocks_capacity_format');

        $form->addCheckbox('mandatory', 'admin.program.blocks_mandatory_form')
            ->addCondition(Form::EQUAL, true)
            ->toggle('autoRegisteredCheckbox');

        $form->addCheckbox('autoRegistered', 'admin.program.blocks_auto_register')
            ->setOption('id', 'autoRegisteredCheckbox')
            ->setAttribute('data-toggle', 'tooltip')
            ->setAttribute('title', $form->getTranslator()->translate('admin.program.blocks_auto_register_note'))
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateAutoRegistered'], 'admin.program.blocks_auto_register_not_allowed');

        $form->addTextArea('perex', 'admin.program.blocks_perex_form')
            ->addCondition(Form::FILLED)
            ->addRule(Form::MAX_LENGTH, 'admin.program.blocks_perex_length', 160);

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
                'mandatory' => $this->block->getMandatory() > 0,
                'autoRegistered' => $this->block->getMandatory() === 2,
                'perex' => $this->block->getPerex(),
                'description' => $this->block->getDescription(),
                'tools' => $this->block->getTools(),
            ]);

            if ($this->subeventsExists) {
                $form->setDefaults([
                    'subevent' => $this->block->getSubevent() ? $this->block->getSubevent()->getId() : null,
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
     * @throws \Throwable
     */
    public function processForm(Form $form, \stdClass $values) : void
    {
        if ($form['cancel']->isSubmittedBy()) {
            return;
        }

        if (! $this->block) {
            if (! $this->settingsRepository->getBoolValue(Settings::IS_ALLOWED_ADD_BLOCK)) {
                return;
            }
            $this->block = new Block();
        } elseif (! $this->user->isAllowedModifyBlock($this->block)) {
            return;
        }

        if ($this->subeventsExists) {
            $subevent = $values['subevent'] !== '' ? $this->subeventRepository->findById($values['subevent']) : null;
            $this->block->setSubevent($subevent);
        } else {
            $this->block->setSubevent($this->subeventRepository->findImplicit());
        }
        $category = $values['category'] !== '' ? $this->categoryRepository->findById($values['category']) : null;
        $lector   = $values['lector'] !== '' ? $this->userRepository->findById($values['lector']) : null;
        $capacity = $values['capacity'] !== '' ? $values['capacity'] : null;
        $mandatory = $values['mandatory'] ? ($values['autoRegistered'] ? ProgramMandatoryType::AUTO_REGISTERED : ProgramMandatoryType::MANDATORY) : ProgramMandatoryType::VOLUNTARY;


        if (! $this->block) {
            $this->programService->createBlock($values['name'], $subevent, $category, $lector, $values['duration'], $capacity, $mandatory, $values['perex'], $values['description'], $values['tools']);
        } else {
            $this->programService->updateBlock($this->block, $values['name'], $subevent, $category, $lector, $values['duration'], $capacity, $mandatory, $values['perex'], $values['description'], $values['tools']);
        }
    }

    /**
     * Ověří, zda může být program automaticky přihlašovaný.
     */
    public function validateAutoRegistered() : bool
    {
        if ($this->block) {
            if ($this->block->getMandatory() !== 2 && ($this->block->getProgramsCount() > 1 ||
                    ($this->block->getProgramsCount() === 1 && $this->programRepository->hasOverlappingProgram(
                        $this->block->getPrograms()->first(),
                        $this->block->getPrograms()->first()->getStart(),
                        $this->block->getPrograms()->first()->getEnd()
                    )
                    )
                )
            ) {
                return false;
            }
        }
        return true;
    }
}
