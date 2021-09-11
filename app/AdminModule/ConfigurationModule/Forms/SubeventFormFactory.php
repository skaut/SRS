<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Services\SubeventService;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\MultiSelectBox;
use Nextras\FormComponents\Controls\DateTimeControl;
use stdClass;

use function md5;
use function mt_rand;
use function uniqid;

/**
 * Formulář pro úpravu podakce.
 */
class SubeventFormFactory
{
    use Nette\SmartObject;

    /**
     * Upravovaná podakce.
     */
    private ?Subevent $subevent = null;

    private EntityManagerInterface $em;

    private BaseFormFactory $baseFormFactory;

    private SubeventRepository $subeventRepository;

    private SubeventService $subeventService;

    public function __construct(
        EntityManagerInterface $em,
        BaseFormFactory $baseFormFactory,
        SubeventRepository $subeventRepository,
        SubeventService $subeventService
    ) {
        $this->em                 = $em;
        $this->baseFormFactory    = $baseFormFactory;
        $this->subeventRepository = $subeventRepository;
        $this->subeventService    = $subeventService;
    }

    /**
     * Vytvoří formulář.
     */
    public function create(int $id): Form
    {
        $this->subevent = $this->subeventRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $nameText = $form->addText('name', 'admin.configuration.subevents_name')
            ->addRule(Form::FILLED, 'admin.configuration.subevents_name_empty');

        $registerableFromDateTime = new DateTimeControl('admin.configuration.subevents.registerable_from');
        $registerableFromDateTime
            ->setHtmlAttribute('data-toggle', 'tooltip')
            ->setHtmlAttribute('data-placement', 'bottom')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.configuration.subevents.registerable_from_note'));
        $form->addComponent($registerableFromDateTime, 'registerableFrom');

        $registerableToDateTime = new DateTimeControl('admin.configuration.subevents.registerable_to');
        $registerableToDateTime
            ->setHtmlAttribute('data-toggle', 'tooltip')
            ->setHtmlAttribute('data-placement', 'bottom')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.configuration.subevents.registerable_to_note'));
        $form->addComponent($registerableToDateTime, 'registerableTo');

        $capacityText = $form->addText('capacity', 'admin.configuration.subevents_capacity')
            ->setHtmlAttribute('data-toggle', 'tooltip')
            ->setHtmlAttribute('data-placement', 'bottom')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.configuration.subevents_capacity_note'));

        $form->addInteger('fee', 'admin.configuration.subevents_fee')
            ->addRule(Form::FILLED, 'admin.configuration.subevents_fee_empty')
            ->addRule(Form::INTEGER, 'admin.configuration.subevents_fee_format');

        if ($this->subevent) {
            $nameText->addRule(Form::IS_NOT_IN, 'admin.configuration.subevents_name_exists', $this->subeventRepository->findOthersNames($id));
            $capacityText
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER, 'admin.configuration.subevents_capacity_format')
                ->addRule(Form::MIN, 'admin.configuration.subevents_capacity_low', $this->subevent->countUsers());

            $subeventsOptions = $this->subeventService->getSubeventsWithoutSubeventOptions($this->subevent->getId());
        } else {
            $nameText->addRule(Form::IS_NOT_IN, 'admin.configuration.subevents_name_exists', $this->subeventRepository->findAllNames());
            $capacityText
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER, 'admin.configuration.subevents_capacity_format')
                ->addRule(Form::MIN, 'admin.configuration.subevents_capacity_low', 0);

            $subeventsOptions = $this->subeventService->getSubeventsOptions();
        }

        $incompatibleSubeventsSelect = $form->addMultiSelect('incompatibleSubevents', 'admin.configuration.subevents_incompatible_subevents', $subeventsOptions);

        $requiredSubeventsSelect = $form->addMultiSelect('requiredSubevents', 'admin.configuration.subevents_required_subevents', $subeventsOptions);

        $incompatibleSubeventsSelect
            ->addCondition(Form::FILLED)
            ->addRule(
                [$this, 'validateIncompatibleAndRequiredCollision'],
                'admin.configuration.subevents_incompatible_collision',
                [$incompatibleSubeventsSelect, $requiredSubeventsSelect]
            );

        $requiredSubeventsSelect
            ->addCondition(Form::FILLED)
            ->addRule(
                [$this, 'validateIncompatibleAndRequiredCollision'],
                'admin.configuration.subevents_required_collision',
                [$incompatibleSubeventsSelect, $requiredSubeventsSelect]
            );

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-warning');

        if ($this->subevent) {
            $form->setDefaults([
                'id' => $id,
                'name' => $this->subevent->getName(),
                'registerableFrom' => $this->subevent->getRegisterableFrom(),
                'registerableTo' => $this->subevent->getRegisterableTo(),
                'capacity' => $this->subevent->getCapacity(),
                'fee' => $this->subevent->getFee(),
                'incompatibleSubevents' => $this->subeventRepository->findSubeventsIds($this->subevent->getIncompatibleSubevents()),
                'requiredSubevents' => $this->subeventRepository->findSubeventsIds($this->subevent->getRequiredSubevents()),
            ]);
        }

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function processForm(Form $form, stdClass $values): void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        if (! $this->subevent) {
            $this->subevent = new Subevent();
        }

        $capacity = $values->capacity !== '' ? $values->capacity : null;

        $this->subevent->setName($values->name);
        $this->subevent->setRegisterableFrom($values->registerableFrom);
        $this->subevent->setRegisterableTo($values->registerableTo);
        $this->subevent->setCapacity($capacity);
        $this->subevent->setFee($values->fee);
        $this->subevent->setIncompatibleSubevents($this->subeventRepository->findSubeventsByIds($values->incompatibleSubevents));
        $this->subevent->setRequiredSubevents($this->subeventRepository->findSubeventsByIds($values->requiredSubevents));

        $this->subeventRepository->save($this->subevent);
    }

    /**
     * Ověří kolize mezi vyžadovanými a nekompatibilními podakcemi.
     *
     * @param int[][] $args
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ConnectionException
     */
    public function validateIncompatibleAndRequiredCollision(MultiSelectBox $field, array $args): bool
    {
        $incompatibleSubevents = $this->subeventRepository->findSubeventsByIds($args[0]);
        $requiredSubevents     = $this->subeventRepository->findSubeventsByIds($args[1]);

        $this->em->getConnection()->beginTransaction();

        if ($this->subevent) {
            $editedSubevent = $this->subevent;
        } else {
            $editedSubevent = new Subevent();
            $editedSubevent->setName(md5(uniqid((string) mt_rand(), true)));
            $this->subeventRepository->save($editedSubevent);
        }

        $editedSubevent->setIncompatibleSubevents($incompatibleSubevents);
        $editedSubevent->setRequiredSubevents($requiredSubevents);

        $valid = true;

        foreach ($this->subeventRepository->findAll() as $subevent) {
            foreach ($subevent->getRequiredSubeventsTransitive() as $requiredSubevent) {
                if ($subevent->getIncompatibleSubevents()->contains($requiredSubevent)) {
                    $valid = false;
                    break;
                }
            }

            if (! $valid) {
                break;
            }
        }

        $this->subeventRepository->save($editedSubevent);

        $this->em->getConnection()->rollBack();

        return $valid;
    }
}
