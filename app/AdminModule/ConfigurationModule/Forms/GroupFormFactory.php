<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Commands\SetSettingDateValue;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Queries\SettingDateValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use stdClass;
use Throwable;

use function assert;

/**
 * Formulář pro nastavení semináře.
 */
class GroupFormFactory
{
    use Nette\SmartObject;

    public function __construct(
        private BaseFormFactory $baseFormFactory,
        private CommandBus $commandBus,
        private QueryBus $queryBus,
    ) {
    }

    /**
     * Vytvoří formulář.
     *
     * @throws Throwable
     */
    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        assert($renderer instanceof Bs4FormRenderer);
        $renderer->wrappers['control']['container'] = 'div class="col-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-5 col-form-label"';

        $form->addText('groupMinMembers', 'admin.configuration.group_min_members')
            ->addRule(Form::FILLED, 'admin.configuration.group_min_members_empty');

        $form->addText('groupMaxMembers', 'admin.configuration.group_max_members')
            ->addRule(Form::FILLED, 'admin.configuration.group_max_members_empty');

        $groupFillTerm = new DateControl('admin.configuration.group_fill_term');
        $groupFillTerm->addRule(Form::FILLED, 'admin.configuration.group_fill_term_empty');
        $form->addComponent($groupFillTerm, 'groupFillTerm');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'groupMinMembers' => $this->queryBus->handle(new SettingStringValueQuery(Settings::GROUP_MIN_MEMBERS)),
            'groupMaxMembers' => $this->queryBus->handle(new SettingStringValueQuery(Settings::GROUP_MAX_MEMBERS)),
            'groupFillTerm' => $this->queryBus->handle(new SettingDateValueQuery(Settings::GROUP_FILL_TERM)),
        ]);

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
        $this->commandBus->handle(new SetSettingStringValue(Settings::GROUP_MIN_MEMBERS, $values->groupMinMembers));
        $this->commandBus->handle(new SetSettingStringValue(Settings::GROUP_MAX_MEMBERS, $values->groupMaxMembers));
        $this->commandBus->handle(new SetSettingDateValue(Settings::GROUP_FILL_TERM, $values->groupFillTerm));
    }

    /**
     * Ověří, že datum začátku semináře je dříve než konce.
     *
     * @param DateTime[] $args
     */
    public function validateSeminarFromDate(DateControl $field, array $args): bool
    {
        return $args[0] <= $args[1];
    }

    /**
     * Ověří, že datum konce semináře je později než začátku.
     *
     * @param DateTime[] $args
     */
    public function validateSeminarToDate(DateControl $field, array $args): bool
    {
        return $args[0] >= $args[1];
    }

    /**
     * Ověří, že datum uzavření registrace je dříve než začátek semináře.
     *
     * @param DateTime[] $args
     */
    public function validateEditRegistrationTo(DateControl $field, array $args): bool
    {
        return $args[0] < $args[1];
    }
}
