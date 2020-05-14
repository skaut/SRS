<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\CustomInput\CustomCheckbox;
use App\Model\Settings\CustomInput\CustomFile;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\CustomInput\CustomSelect;
use App\Model\Settings\CustomInput\CustomText;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;
use stdClass;
use function explode;
use function implode;
use function trim;

/**
 * Formulář pro úpravu vlastních polí přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomInputFormFactory
{
    use Nette\SmartObject;

    /**
     * Upravované pole.
     */
    private ?CustomInput $customInput = null;

    private BaseFormFactory $baseFormFactory;

    private CustomInputRepository $customInputRepository;

    public function __construct(BaseFormFactory $baseFormFactory, CustomInputRepository $customInputRepository)
    {
        $this->baseFormFactory       = $baseFormFactory;
        $this->customInputRepository = $customInputRepository;
    }

    /**
     * Vytvoří formulář.
     */
    public function create(int $id) : Form
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
            ->setHtmlAttribute('class', 'btn btn-warning');

        if ($this->customInput) {
            $typeSelect->setDisabled();
            $optionsText->setDisabled();

            $form->setDefaults([
                'id' => $id,
                'name' => $this->customInput->getName(),
                'type' => $this->customInput->getType(),
                'mandatory' => $this->customInput->isMandatory(),
            ]);

            if ($this->customInput instanceof CustomSelect) {
                $customInput = $this->customInput;
                $optionsText->setDefaultValue($customInput->getOptions());
            }
        }

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        if (! $this->customInput) {
            switch ($values->type) {
                case CustomInput::TEXT:
                    $this->customInput = new CustomText();
                    break;

                case CustomInput::CHECKBOX:
                    $this->customInput = new CustomCheckbox();
                    break;

                case CustomInput::SELECT:
                    $this->customInput = new CustomSelect();

                    $options        = explode(',', $values->options);
                    $optionsTrimmed = [];
                    foreach ($options as $option) {
                        $optionsTrimmed[] = trim($option);
                    }

                    $this->customInput->setOptions(implode(', ', $optionsTrimmed));

                    break;

                case CustomInput::FILE:
                    $this->customInput = new CustomFile();
                    break;
            }
        }

        $this->customInput->setName($values->name);
        $this->customInput->setMandatory($values->mandatory);

        $this->customInputRepository->save($this->customInput);
    }

    /**
     * Vrátí typy vlastních polí jako možnosti pro select.
     *
     * @return string[]
     */
    private function prepareCustomInputTypesOptions() : array
    {
        $options = [];
        foreach (CustomInput::$types as $type) {
            $options[$type] = 'admin.common.custom_' . $type;
        }

        return $options;
    }
}
