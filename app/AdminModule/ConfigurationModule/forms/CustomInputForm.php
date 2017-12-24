<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\CustomInput\CustomCheckbox;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\CustomInput\CustomSelect;
use App\Model\Settings\CustomInput\CustomText;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro úpravu vlastních polí přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomInputForm extends Nette\Object
{
    /**
     * Upravované pole.
     * @var CustomInput
     */
    private $customInput;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var CustomInputRepository */
    private $customInputRepository;


    /**
     * CustomInputForm constructor.
     * @param BaseForm $baseFormFactory
     * @param CustomInputRepository $customInputRepository
     */
    public function __construct(BaseForm $baseFormFactory, CustomInputRepository $customInputRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->customInputRepository = $customInputRepository;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @return Form
     */
    public function create($id)
    {
        $this->customInput = $this->customInputRepository->findById($id);


        $form = $this->baseFormFactory->create();


        $form->addHidden('id');

        $form->addText('name', 'admin.configuration.custom_inputs_name')
            ->addRule(Form::FILLED, 'admin.configuration.custom_inputs_name_empty');

        $typeSelect = $form->addSelect('type', 'admin.configuration.custom_inputs_type', $this->prepareCustomInputTypesOptions());
        $typeSelect->addCondition($form::EQUAL, CustomInput::SELECT)->toggle('custom-input-select');

        $form->addCheckbox('mandatory', 'admin.configuration.custom_inputs_edit_mandatory');

        $optionsText = $form->addText('options', 'admin.configuration.custom_inputs_edit_options');
        $optionsText->setOption('id', 'custom-input-select');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setAttribute('class', 'btn btn-warning');


        if ($this->customInput) {
            $typeSelect->setDisabled();
            $optionsText->setDisabled();

            $form->setDefaults([
                'id' => $id,
                'name' => $this->customInput->getName(),
                'type' => $this->customInput->getType(),
                'mandatory' => $this->customInput->isMandatory(),
            ]);

            if ($this->customInput->getType() == CustomInput::SELECT)
                $optionsText->setDefaultValue($this->customInput->getOptions());
        }

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function processForm(Form $form, \stdClass $values)
    {
        if (!$form['cancel']->isSubmittedBy()) {
            if (!$this->customInput) {
                switch ($values['type']) {
                    case CustomInput::TEXT:
                        $this->customInput = new CustomText();
                        break;

                    case CustomInput::CHECKBOX:
                        $this->customInput = new CustomCheckbox();
                        break;

                    case CustomInput::SELECT:
                        $this->customInput = new CustomSelect();

                        $options = explode(',', $values['options']);
                        $optionsCleaned = [];
                        foreach ($options as $option)
                            $optionsCleaned[] = trim($option);
                        $this->customInput->setOptions(implode(', ', $optionsCleaned));

                        break;
                }
            }

            $this->customInput->setName($values['name']);
            $this->customInput->setMandatory($values['mandatory']);

            $this->customInputRepository->save($this->customInput);
        }
    }

    /**
     * Vrátí typy vlastních polí jako možnosti pro select.
     * @return array
     */
    private function prepareCustomInputTypesOptions()
    {
        $options = [];
        foreach (CustomInput::$types as $type)
            $options[$type] = 'admin.common.custom_' . $type;
        return $options;
    }
}
