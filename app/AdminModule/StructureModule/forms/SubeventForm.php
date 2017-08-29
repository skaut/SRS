<?php

namespace App\AdminModule\StructureModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\ACL\Permission;
use App\Model\ACL\PermissionRepository;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\CMS\PageRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Structure\Subevent;
use App\Model\Structure\SubeventRepository;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro úpravu podakce.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SubeventForm extends Nette\Object
{
    /**
     * Upravovaná role.
     * @var Subevent
     */
    private $subevent;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var SubeventRepository */
    private $subeventRepository;

    /**
     * SubeventForm constructor.
     * @param BaseForm $baseFormFactory
     * @param SubeventRepository $subeventRepository
     */
    public function __construct(BaseForm $baseFormFactory, SubeventRepository $subeventRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->subeventRepository = $subeventRepository;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @return Form
     */
    public function create($id)
    {
        $this->subevent = $this->subeventRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addText('name', 'admin.structure.subevents_name')
            ->addRule(Form::FILLED, 'admin.structure.subevents_name_empty')
            ->addRule(Form::IS_NOT_IN, 'admin.structure.subevents_name_exists', $this->roleRepository->findOthersNames($id));

        $form->addText('capacity', 'admin.structure.subevents_capacity')
            ->setAttribute('data-toggle', 'tooltip')
            ->setAttribute('title', $form->getTranslator()->translate('admin.structure.subevents_capacity_note'))
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.structure.subevents_format')
            ->addRule(Form::MIN, 'admin.structure.subevents_capacity_low', $this->subeventRepository->countApprovedUsersInSubevent($this->subevent));

        $form->addText('fee', 'admin.structure.subevents_fee')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.structure.subevents_fee_format');

        $subeventsOptions = $this->subeventRepository->getSubeventsWithoutSubeventOptions($this->subevent->getId());

        $incompatibleSubeventsSelect = $form->addMultiSelect('incompatibleSubevents', 'admin.structure.subevents_incompatible_subevents', $subeventsOptions);

        $requiredSubeventsSelect = $form->addMultiSelect('requiredSubevents', 'admin.structure.subevents_required_subevents', $subeventsOptions);

        $incompatibleSubeventsSelect
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateIncompatibleAndRequiredCollision'],
                'admin.structure.subevents_incompatible_collision', [$incompatibleSubeventsSelect, $requiredSubeventsSelect]);

        $requiredSubeventsSelect
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateIncompatibleAndRequiredCollision'],
                'admin.structure.subevents_required_collision', [$incompatibleSubeventsSelect, $requiredSubeventsSelect]);

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setAttribute('class', 'btn btn-warning');

        if ($this->subevent) {
            $form->setDefaults([
                'id' => $id,
                'name' => $this->subevent->getName(),
                'capacity' => $this->subevent->getCapacity(),
                'fee' => $this->subevent->getFee(),
                'incompatibleSubevents' => $this->subeventRepository->findSubeventsIds($this->subevent->getIncompatibleSubevents()),
                'requiredSubevents' => $this->subeventRepository->findSubeventsIds($this->subevent->getRequiredSubevents())
            ]);
        }

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
            if (!$this->subevent)
                $this->subevent = new Subevent();

            $capacity = $values['capacity'] !== '' ? $values['capacity'] : NULL;

            $this->subevent->setName($values['name']);
            $this->subevent->setCapacity($capacity);
            $this->subevent->setFee($values['fee']);
            $this->subevent->setIncompatibleSubevents($this->subeventRepository->findSubeventsByIds($values['incompatibleSubevents']));
            $this->subevent->setRequiredSubevents($this->subeventRepository->findSubeventsByIds($values['requiredSubevents']));

            $this->subeventRepository->save($this->subevent);
        }
    }

    /**
     * Ověří kolize mezi vyžadovanými a nekompatibilními podakcemi.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateIncompatibleAndRequiredCollision($field, $args)
    {
        $incompatibleSubevents = $this->subeventRepository->findSubeventsByIds($args[0]);
        $requiredSubevents = $this->subeventRepository->findSubeventsByIds($args[1]);

        $this->subeventRepository->getEntityManager()->getConnection()->beginTransaction();

        $this->subevent->setIncompatibleSubevents($incompatibleSubevents);
        $this->subevent->setRequiredSubevents($requiredSubevents);

        $valid = TRUE;

        foreach ($this->subeventRepository->findAll() as $subevent) {
            foreach ($subevent->getRequiredSubeventsTransitive() as $requiredSubevent) {
                if ($subevent->getIncompatibleSubevents()->contains($requiredSubevent)) {
                    $valid = FALSE;
                    break;
                }
            }
            if (!$valid)
                break;
        }

        $this->subeventRepository->getEntityManager()->getConnection()->rollBack();

        return $valid;
    }
}
