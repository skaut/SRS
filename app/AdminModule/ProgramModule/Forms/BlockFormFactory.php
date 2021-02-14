<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\Commands\SaveBlock;
use App\Model\Program\Exceptions\BlockCapacityInsufficientException;
use App\Model\Program\Queries\MinBlockAllowedCapacityQuery;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\CategoryRepository;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\CommandBus;
use App\Services\QueryBus;
use App\Services\SubeventService;
use App\Utils\Helpers;
use App\Utils\Validators;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\Controls\TextInput;
use stdClass;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

use function assert;

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

    private QueryBus $queryBus;

    private BaseFormFactory $baseFormFactory;

    private BlockRepository $blockRepository;

    private UserRepository $userRepository;

    private CategoryRepository $categoryRepository;

    private SubeventRepository $subeventRepository;

    private SubeventService $subeventService;

    private Validators $validators;

    public function __construct(
        CommandBus $commandBus,
        QueryBus $queryBus,
        BaseFormFactory $baseFormFactory,
        BlockRepository $blockRepository,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        SubeventRepository $subeventRepository,
        SubeventService $subeventService,
        Validators $validators
    ) {
        $this->commandBus         = $commandBus;
        $this->queryBus           = $queryBus;
        $this->baseFormFactory    = $baseFormFactory;
        $this->blockRepository    = $blockRepository;
        $this->userRepository     = $userRepository;
        $this->categoryRepository = $categoryRepository;
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
    public function create(int $id, int $userId): Form
    {
        $this->block = $this->blockRepository->findById($id);
        $this->user  = $this->userRepository->findById($userId);

        $this->subeventsExists = $this->subeventRepository->explicitSubeventsExists();

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addText('name', 'admin.program.blocks.common.name')
            ->addRule(Form::FILLED, 'admin.program.blocks.form.name_empty');

        if ($this->subeventsExists) {
            $form->addSelect('subevent', 'admin.program.blocks.common.subevent', $this->subeventService->getSubeventsOptions())
                ->setPrompt('')
                ->addRule(Form::FILLED, 'admin.program.blocks.form.subevent_empty');
        }

        $form->addSelect('category', 'admin.program.blocks.common.category', $this->categoryRepository->getCategoriesOptions())
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

        $form->addMultiSelect('lectors', 'admin.program.blocks.common.lectors', $lectorsOptions);

        $form->addInteger('duration', 'admin.program.blocks.form.duration')
            ->addRule(Form::FILLED, 'admin.program.blocks.form.duration_empty')
            ->addRule(Form::INTEGER, 'admin.program.blocks.form.duration_format');

        $capacityText = $form->addText('capacity', 'admin.program.blocks.common.capacity')
            ->setHtmlAttribute('data-toggle', 'tooltip')
            ->setHtmlAttribute('data-placement', 'bottom')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.program.blocks.form.capacity_note'));
        $capacityText->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.program.blocks.form.capacity_format')
            ->toggle('alternatesAllowedCheckbox');
        $minAllowedCapacity = $this->queryBus->handle(new MinBlockAllowedCapacityQuery($this->block));
        if ($minAllowedCapacity !== null) {
            $capacityText->addCondition(Form::FILLED)
                ->addRule(Form::MIN, 'admin.program.blocks.form.capacity_low', $minAllowedCapacity);
        }

        $form->addCheckbox('alternatesAllowed', 'admin.program.blocks.form.alternates_allowed')
            ->setOption('id', 'alternatesAllowedCheckbox');

        $form->addCheckbox('mandatory', 'admin.program.blocks.form.mandatory')
            ->addCondition(Form::EQUAL, true)
            ->toggle('autoRegisteredCheckbox');

        $form->addCheckbox('autoRegistered', 'admin.program.blocks.form.auto_registered')
            ->setOption('id', 'autoRegisteredCheckbox')
            ->setHtmlAttribute('data-toggle', 'tooltip')
            ->setHtmlAttribute('data-placement', 'bottom')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.program.blocks.form.auto_registered_note'))
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateAutoRegistered'], 'admin.program.blocks.form.auto_registered_not_allowed', [$capacityText]);

        $form->addTextArea('perex', 'admin.program.blocks.form.perex')
            ->addCondition(Form::FILLED)
            ->addRule(Form::MAX_LENGTH, 'admin.program.blocks.form.perex_length', 160);

        $form->addTextArea('description', 'admin.program.blocks.common.description')
            ->setHtmlAttribute('class', 'tinymce-paragraph');

        $form->addText('tools', 'admin.program.blocks.common.tools');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-warning');

        $nameText = $form['name'];
        assert($nameText instanceof TextInput);

        if ($this->block) {
            $nameText->addRule(Form::IS_NOT_IN, 'admin.program.blocks.form.name_exists', $this->blockRepository->findOthersNames($id));

            $form->setDefaults([
                'id' => $id,
                'name' => $this->block->getName(),
                'category' => $this->block->getCategory() ? $this->block->getCategory()->getId() : null,
                'lectors' => Helpers::getIds($this->block->getLectors()),
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
            $nameText->addRule(Form::IS_NOT_IN, 'admin.program.blocks.form.name_exists', $this->blockRepository->findAllNames());

            if (! $userIsAllowedManageAllPrograms) {
                $lectorsMultiSelect = $form['lectors'];
                assert($lectorsMultiSelect instanceof MultiSelectBox);
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
    public function processForm(Form $form, stdClass $values): void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            $form->getPresenter()->redirect('Blocks:default');
        }

        if ($this->block === null) {
            if (! $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_ADD_BLOCK))) {
                $form->getPresenter()->flashMessage('admin.program.blocks.message.add_not_allowed', 'danger');
                $form->getPresenter()->redirect('Blocks:default');
            }
        } else {
            if (! $this->user->isAllowedModifyBlock($this->block)) {
                $form->getPresenter()->flashMessage('admin.program.blocks.message.edit_not_allowed', 'danger');
                $form->getPresenter()->redirect('Blocks:default');
            }
        }

        if ($this->subeventsExists) {
            $subevent = $values->subevent !== '' ? $this->subeventRepository->findById($values->subevent) : null;
        } else {
            $subevent = $this->subeventRepository->findImplicit();
        }

        $category          = $values->category !== '' ? $this->categoryRepository->findById($values->category) : null;
        $lectors           = $this->userRepository->findUsersByIds($values->lectors);
        $capacity          = $values->capacity !== '' ? $values->capacity : null;
        $alternatesAllowed = $capacity !== null && $values->alternatesAllowed;
        $mandatory         = $values->mandatory
            ? ($values->autoRegistered ? ProgramMandatoryType::AUTO_REGISTERED : ProgramMandatoryType::MANDATORY)
            : ProgramMandatoryType::VOLUNTARY;

        $blockOld = null;

        if ($this->block === null) {
            $this->block = new Block($values->name, $values->duration, $capacity, $alternatesAllowed, $mandatory);
            $this->block->setSubevent($subevent);
            $this->block->setCategory($category);
            $this->block->setLectors($lectors);
            $this->block->setPerex($values->perex);
            $this->block->setDescription($values->description);
            $this->block->setTools($values->tools);
        } else {
            $blockOld = clone $this->block;

            $this->block->setName($values->name);
            $this->block->setSubevent($subevent);
            $this->block->setCategory($category);
            $this->block->setLectors($lectors);
            $this->block->setDuration($values->duration);
            $this->block->setCapacity($capacity);
            $this->block->setAlternatesAllowed($alternatesAllowed);
            $this->block->setMandatory($mandatory);
            $this->block->setPerex($values->perex);
            $this->block->setDescription($values->description);
            $this->block->setTools($values->tools);
        }

        try {
            $this->commandBus->handle(new SaveBlock($this->block, $blockOld));

            $form->getPresenter()->flashMessage('admin.program.blocks.message.save_success', 'success');

            if ($form->isSubmitted() === $form['submitAndContinue']) {
                $form->getPresenter()->redirect('Blocks:edit', ['id' => $this->block->getId()]);
            } else {
                $form->getPresenter()->redirect('Blocks:default');
            }
        } catch (HandlerFailedException $e) {
            if ($e->getPrevious() instanceof BlockCapacityInsufficientException) {
                $form->getPresenter()->flashMessage('admin.program.blocks.message.capacity_low', 'danger');
            } else {
                $form->getPresenter()->flashMessage('admin.program.blocks.message.save_failed', 'danger');
            }
        }
    }

    /**
     * Ověří, zda může být program automaticky přihlašovaný.
     *
     * @param string[]|int[] $args
     */
    public function validateAutoRegistered(Checkbox $field, array $args): bool
    {
        $capacity = $args[0] === '' ? null : $args[0];

        if ($this->block) {
            return $this->validators->validateBlockAutoRegistered($this->block, $capacity);
        }

        return true;
    }
}
