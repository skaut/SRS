<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Commands\SetSettingDateValue;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Queries\SettingDateValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
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
class SeminarFormFactory
{
    use Nette\SmartObject;

    private BaseFormFactory $baseFormFactory;

    private CommandBus $commandBus;

    private QueryBus $queryBus;

    private SubeventRepository $subeventRepository;

    public function __construct(BaseFormFactory $baseForm, CommandBus $commandBus, QueryBus $queryBus, SubeventRepository $subeventRepository)
    {
        $this->baseFormFactory    = $baseForm;
        $this->commandBus         = $commandBus;
        $this->queryBus           = $queryBus;
        $this->subeventRepository = $subeventRepository;
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

        $form->addText('seminarName', 'admin.configuration.seminar_name')
            ->addRule(Form::FILLED, 'admin.configuration.seminar_name_empty');

        $seminarFromDate = new DateControl('admin.configuration.seminar_from_date');
        $seminarFromDate->addRule(Form::FILLED, 'admin.configuration.seminar_from_date_empty');
        $form->addComponent($seminarFromDate, 'seminarFromDate');

        $seminarToDate = new DateControl('admin.configuration.seminar_to_date');
        $seminarToDate->addRule(Form::FILLED, 'admin.configuration.seminar_to_date_empty');
        $form->addComponent($seminarToDate, 'seminarToDate');

        $editRegistrationTo = new DateControl('admin.configuration.edit_registration_to');
        $editRegistrationTo->addRule(Form::FILLED, 'admin.configuration.edit_registration_to_empty');
        $form->addComponent($editRegistrationTo, 'editRegistrationTo');

        $seminarFromDate->addRule([$this, 'validateSeminarFromDate'], 'admin.configuration.seminar_from_date_after_to', [$seminarFromDate, $seminarToDate]);
        $seminarToDate->addRule([$this, 'validateSeminarToDate'], 'admin.configuration.seminar_to_date_before_from', [$seminarToDate, $seminarFromDate]);
        $editRegistrationTo->addRule([$this, 'validateEditRegistrationTo'], 'admin.configuration.edit_registration_to_after_from', [$editRegistrationTo, $seminarFromDate]);

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'seminarName' => $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME)),
            'seminarFromDate' => $this->queryBus->handle(new SettingDateValueQuery(Settings::SEMINAR_FROM_DATE)),
            'seminarToDate' => $this->queryBus->handle(new SettingDateValueQuery(Settings::SEMINAR_TO_DATE)),
            'editRegistrationTo' => $this->queryBus->handle(new SettingDateValueQuery(Settings::EDIT_REGISTRATION_TO)),
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
        $this->commandBus->handle(new SetSettingStringValue(Settings::SEMINAR_NAME, $values->seminarName));
        $implicitSubevent = $this->subeventRepository->findImplicit();
        $implicitSubevent->setName($values->seminarName);
        $this->subeventRepository->save($implicitSubevent);

        $this->commandBus->handle(new SetSettingDateValue(Settings::SEMINAR_FROM_DATE, $values->seminarFromDate));
        $this->commandBus->handle(new SetSettingDateValue(Settings::SEMINAR_TO_DATE, $values->seminarToDate));
        $this->commandBus->handle(new SetSettingDateValue(Settings::EDIT_REGISTRATION_TO, $values->editRegistrationTo));
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
