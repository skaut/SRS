<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\Commands\SaveBlock;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\CategoryRepository;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\CommandBus;
use App\Services\SettingsService;
use App\Services\SubeventService;
use App\Utils\Validators;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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
     */
    private ?User $user = null;

    /**
     * Upravovaný programový blok.
     */
    private ?Block $block = null;

    /**
     * Jsou vytvořené podakce.
     */
    private bool $subeventsExists;

    private CommandBus $commandBus;

    private BaseFormFactory $baseFormFactory;

    private BlockRepository $blockRepository;

    private UserRepository $userRepository;

    private CategoryRepository $categoryRepository;

    private SettingsService $settingsService;

    private SubeventRepository $subeventRepository;

    private SubeventService $subeventService;

    private Validators $validators;

    public function __construct(
        CommandBus $commandBus,
        BaseFormFactory $baseFormFactory,
        BlockRepository $blockRepository,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        SettingsService $settingsService,
        SubeventRepository $subeventRepository,
        SubeventService $subeventService,
        Validators $validators
    ) {
        $this->commandBus         = $commandBus;
        $this->baseFormFactory    = $baseFormFactory;
        $this->blockRepository    = $blockRepository;
        $this->userRepository     = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->settingsService    = $settingsService;
        $this->subeventRepository = $subeventRepository;
        $this->subeventService    = $subeventService;
        $this->validators         = $validators;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
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

        $form->addInteger('duration', 'admin.program.blocks_duration_form')
            ->addRule(Form::FILLED, 'admin.program.blocks_duration_empty')
            ->addRule(Form::INTEGER, 'admin.program.blocks_duration_format');

        $form->addText('capacity', 'admin.program.blocks_capacity')
            ->setHtmlAttribute('data-toggle', 'tooltip')
            ->setHtmlAttribute('data-placement', 'bottom')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.program.blocks_capacity_note'))
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.program.blocks_capacity_format')
            ->toggle('alternates-allowed');

        $form->addCheckbox('alternatesAllowed', 'admin.program.blocks_alternates_allowed')
            ->setOption('id', 'alternates-allowed');

        $form->addCheckbox('mandatory', 'admin.program.blocks_mandatory_form')
            ->addCondition(Form::EQUAL, true)
            ->toggle('autoRegisteredCheckbox');

        $form->addCheckbox('autoRegistered', 'admin.program.blocks_auto_registered')
            ->setOption('id', 'autoRegisteredCheckbox')
            ->setHtmlAttribute('data-toggle', 'tooltip')
            ->setHtmlAttribute('data-placement', 'bottom')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.program.blocks_auto_registered_note'))
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateAutoRegistered'], 'admin.program.blocks_auto_registered_not_allowed'); //todo: nesmi mit omezenou kapacitu

        $form->addTextArea('perex', 'admin.program.blocks_perex_form')
            ->addCondition(Form::FILLED)
            ->addRule(Form::MAX_LENGTH, 'admin.program.blocks_perex_length', 160);

        $form->addTextArea('description', 'admin.program.blocks_description')
            ->setHtmlAttribute('class', 'tinymce-paragraph');

        $form->addText('tools', 'admin.program.blocks_tools');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-warning');

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
                'alternatesAllowed' => $this->block->isAlternatesAllowed(),
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
     *
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        if ($this->block === null) {
            if (! $this->settingsService->getBoolValue(Settings::IS_ALLOWED_ADD_BLOCK)) {
                return; //todo: exception
            }
        } else {
            if (! $this->user->isAllowedModifyBlock($this->block)) {
                return; //todo: exception
            }
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

        if ($this->block === null) {
            $this->block = new Block($values->name, $values->duration, $capacity, $values->alternatesAllowed, $mandatory, $subevent, $category);
            $this->block->setLectors($lectors);
            $this->block->setPerex($values->perex);
            $this->block->setDescription($values->description);
            $this->block->setTools($values->tools);
        } else {
            $this->block->setName($values->name);
            $this->block->setSubevent($subevent);
            $this->block->setCategory($category);
            $this->block->setLectors($lectors);
            $this->block->setDuration($values->duration);
            $this->block->setCapacity($capacity);
            $this->block->setAlternatesAllowed($values->alternatesAllowed);
            $this->block->setMandatory($mandatory);
            $this->block->setPerex($values->perex);
            $this->block->setDescription($values->description);
            $this->block->setTools($values->tools);
        }

        $this->commandBus->handle(new SaveBlock($this->block));
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
