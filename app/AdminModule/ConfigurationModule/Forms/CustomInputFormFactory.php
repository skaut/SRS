<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\CustomInput\CustomCheckbox;
use App\Model\CustomInput\CustomDate;
use App\Model\CustomInput\CustomDateTime;
use App\Model\CustomInput\CustomFile;
use App\Model\CustomInput\CustomInput;
use App\Model\CustomInput\CustomMultiSelect;
use App\Model\CustomInput\CustomSelect;
use App\Model\CustomInput\CustomText;
use App\Model\CustomInput\Repositories\CustomInputRepository;
use App\Services\AclService;
use App\Utils\Helpers;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette;
use Nette\Application\UI\Form;
use stdClass;

use function array_keys;
use function array_map;
use function explode;
use function trim;

/**
 * Formulář pro úpravu vlastních polí přihlášky.
 */
class CustomInputFormFactory
{
    use Nette\SmartObject;

    /**
     * Upravované pole.
     */
    private CustomInput|null $customInput = null;

    public function __construct(private readonly BaseFormFactory $baseFormFactory, private readonly CustomInputRepository $customInputRepository, private readonly AclService $aclService, private readonly RoleRepository $roleRepository)
    {
    }

    /**
     * Vytvoří formulář.
     */
    public function create(int $id): Form
    {
        $this->customInput = $this->customInputRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addText('name', 'admin.configuration.custom_inputs_name')
            ->addRule(Form::FILLED, 'admin.configuration.custom_inputs_name_empty');

        $rolesOptions = $this->aclService->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED]);
        $rolesSelect  = $form->addMultiSelect('roles', 'admin.configuration.custom_inputs_roles', $rolesOptions)
            ->addRule(Form::FILLED, 'admin.configuration.custom_inputs_roles_empty');

        $typeSelect = $form->addSelect('type', 'admin.configuration.custom_inputs_type', $this->prepareCustomInputTypesOptions());
        $typeSelect->addCondition($form::EQUAL, CustomInput::SELECT)->toggle('custom-input-select');
        $typeSelect->addCondition($form::EQUAL, CustomInput::MULTISELECT)->toggle('custom-input-select');

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
                'name' => $this->customInput->getName(),
                'roles' => Helpers::getIds($this->customInput->getRoles()),
                'type' => $this->customInput->getType(),
                'mandatory' => $this->customInput->isMandatory(),
            ]);

            if ($this->customInput instanceof CustomSelect || $this->customInput instanceof CustomMultiSelect) {
                $customInput = $this->customInput;
                $optionsText->setDefaultValue($customInput->getOptionsText());
            }
        } else {
            $rolesSelect->setDefaultValue(array_keys($rolesOptions));
        }

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function processForm(Form $form, stdClass $values): void
    {
        if ($form->isSubmitted() == $form['cancel']) {
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
                    $options           = array_map(
                        static fn (string $o) => trim($o),
                        explode(',', $values->options),
                    );
                    $this->customInput->setOptions($options);
                    break;

                case CustomInput::MULTISELECT:
                    $this->customInput = new CustomMultiSelect();
                    $options           = array_map(
                        static fn (string $o) => trim($o),
                        explode(',', $values->options),
                    );
                    $this->customInput->setOptions($options);
                    break;

                case CustomInput::FILE:
                    $this->customInput = new CustomFile();
                    break;

                case CustomInput::DATE:
                    $this->customInput = new CustomDate();
                    break;

                case CustomInput::DATETIME:
                    $this->customInput = new CustomDateTime();
                    break;
            }
        }

        $this->customInput->setName($values->name);
        $this->customInput->setRoles($this->roleRepository->findRolesByIds($values->roles));
        $this->customInput->setMandatory($values->mandatory);

        $this->customInputRepository->save($this->customInput);
    }

    /**
     * Vrátí typy vlastních polí jako možnosti pro select.
     *
     * @return string[]
     */
    private function prepareCustomInputTypesOptions(): array
    {
        $options = [];
        foreach (CustomInput::$types as $type) {
            $options[$type] = 'admin.common.custom_' . $type;
        }

        return $options;
    }
}
