<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\AdminModule\Forms\BaseFormFactory;
use App\Model\ACL\Permission;
use App\Model\ACL\SrsResource;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\CategoryRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Structure\SubeventRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ProgramService;
use App\Services\SettingsService;
use App\Services\SubeventService;
use App\Utils\Validators;
use Doctrine\ORM\NonUniqueResultException;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\Controls\TextInput;
use stdClass;
use Throwable;

/**
 * Formulář pro úpravu programového bloku.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BlockFormFactory
{
    use Nette\SmartObject;

    /**
     * Přihlášený uživatel.
     * @var User
     */
    private $user;

    /**
     * Upravovaný programový blok.
     * @var ?Block
     */
    private $block;

    /**
     * Jsou vytvořené podakce.
     * @var bool
     */
    private $subeventsExists;

    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var BlockRepository */
    private $blockRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var CategoryRepository */
    private $categoryRepository;

    /** @var SettingsService */
    private $settingsService;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var ProgramService */
    private $programService;

    /** @var SubeventService */
    private $subeventService;

    /** @var Validators */
    private $validators;


    public function __construct(
        BaseFormFactory $baseFormFactory,
        BlockRepository $blockRepository,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        SettingsService $settingsService,
        ProgramRepository $programRepository,
        SubeventRepository $subeventRepository,
        ProgramService $programService,
        SubeventService $subeventService,
        Validators $validators
    ) {
        $this->baseFormFactory    = $baseFormFactory;
        $this->blockRepository    = $blockRepository;
        $this->userRepository     = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->settingsService    = $settingsService;
        $this->programRepository  = $programRepository;
        $this->subeventRepository = $subeventRepository;
        $this->programService     = $programService;
        $this->subeventService    = $subeventService;
        $this->validators         = $validators;
    }

    /**
     * Vytvoří formulář.
     * @throws NonUniqueResultException
     */
    public function create(int $id, int $userId) : BaseForm
    {
        $this->block = $this->blockRepository->findById($id);
        $this->user  = $this->userRepository->findById($userId);

        $this->subeventsExists = $this->subeventRepository->explicitSubeventsExists();

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addText('name', 'admin.program.blocks_name')
            ->addRule(Form::FILLED, 'admin.program.blocks_name_empty');

        if ($this->subeventsExists) {
            $form->addSelect('subevent', 'admin.program.blocks_subevent', $this->subeventService->getSubeventsOptions())
                ->setPrompt('')
                ->addRule(Form::FILLED, 'admin.program.blocks_subevent_empty');
        }

        $form->addSelect('category', 'admin.program.blocks_category', $this->categoryRepository->getCategoriesOptions())
            ->setPrompt('');

        $userIsAllowedManageAllPrograms = $this->user->isAllowed(SrsResource::PROGRAM, Permission::MANAGE_ALL_PROGRAMS);
        if ($userIsAllowedManageAllPrograms) {
            $lectorsOptions = $this->userRepository->getLectorsOptions();
        } else {
            if ($this->block) {
                $lectorsOptions = [];
                foreach ($this->block->getLectors() as $lector) {
                    $lectorsOptions[$lector->getId()] = $lector->getDisplayName();
                }
            } else {
                $lectorsOptions[$this->user->getId()] = $this->user->getDisplayName();
            }
        }

        $form->addMultiSelect('lectors', 'admin.program.blocks_lectors', $lectorsOptions);

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

        $form->addCheckbox('autoRegistered', 'admin.program.blocks_auto_registered')
            ->setOption('id', 'autoRegisteredCheckbox')
            ->setAttribute('data-toggle', 'tooltip')
            ->setAttribute('title', $form->getTranslator()->translate('admin.program.blocks_auto_registered_note'))
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateAutoRegistered'], 'admin.program.blocks_auto_registered_not_allowed');

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

        /** @var TextInput $nameText */
        $nameText = $form['name'];

        if ($this->block) {
            $nameText->addRule(Form::IS_NOT_IN, 'admin.program.blocks_name_exists', $this->blockRepository->findOthersNames($id));

            $form->setDefaults([
                'id' => $id,
                'name' => $this->block->getName(),
                'category' => $this->block->getCategory() ? $this->block->getCategory()->getId() : null,
                'lectors' => $this->userRepository->findUsersIds($this->block->getLectors()),
                'duration' => $this->block->getDuration(),
                'capacity' => $this->block->getCapacity(),
                'mandatory' => $this->block->getMandatory() === ProgramMandatoryType::MANDATORY || $this->block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED,
                'autoRegistered' => $this->block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED,
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
            $nameText->addRule(Form::IS_NOT_IN, 'admin.program.blocks_name_exists', $this->blockRepository->findAllNames());

            if (! $userIsAllowedManageAllPrograms) {
                /** @var MultiSelectBox $lectorsMultiSelect */
                $lectorsMultiSelect = $form['lectors'];
                $lectorsMultiSelect->setDefaultValue([$this->user->getId()]);
            }
        }

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @throws Throwable
     */
    public function processForm(BaseForm $form, stdClass $values) : void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        if (! $this->block) {
            if (! $this->settingsService->getBoolValue(Settings::IS_ALLOWED_ADD_BLOCK)) {
                return;
            }
        } elseif (! $this->user->isAllowedModifyBlock($this->block)) {
            return;
        }

        if ($this->subeventsExists) {
            $subevent = $values->subevent !== '' ? $this->subeventRepository->findById($values->subevent) : null;
        } else {
            $subevent = $this->subeventRepository->findImplicit();
        }
        $category  = $values->category !== '' ? $this->categoryRepository->findById($values->category) : null;
        $lectors   = $this->userRepository->findUsersByIds($values->lectors);
        $capacity  = $values->capacity !== '' ? $values->capacity : null;
        $mandatory = $values->mandatory ? ($values->autoRegistered ? ProgramMandatoryType::AUTO_REGISTERED : ProgramMandatoryType::MANDATORY) : ProgramMandatoryType::VOLUNTARY;

        if (! $this->block) {
            $this->programService->createBlock($values->name, $subevent, $category, $lectors, $values->duration, $capacity, $mandatory, $values->perex, $values->description, $values->tools);
        } else {
            $this->programService->updateBlock($this->block, $values->name, $subevent, $category, $lectors, $values->duration, $capacity, $mandatory, $values->perex, $values->description, $values->tools);
        }
    }

    /**
     * Ověří, zda může být program automaticky přihlašovaný.
     */
    public function validateAutoRegistered() : bool
    {
        if ($this->block) {
            return $this->validators->validateBlockAutoRegistered($this->block);
        }
        return true;
    }
}
