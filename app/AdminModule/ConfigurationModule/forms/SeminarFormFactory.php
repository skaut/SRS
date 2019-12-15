<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Structure\SubeventRepository;
use App\Services\SettingsService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use Nextras\Forms\Controls\DatePicker;
use Nextras\Forms\Rendering\Bs3FormRenderer;
use stdClass;
use Throwable;

/**
 * Formulář pro nastavení semináře.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SeminarFormFactory
{
    use Nette\SmartObject;

    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var SettingsService */
    private $settingsService;

    /** @var SubeventRepository */
    private $subeventRepository;


    public function __construct(
        BaseFormFactory $baseForm,
        SettingsService $settingsService,
        SubeventRepository $subeventRepository
    ) {
        $this->baseFormFactory    = $baseForm;
        $this->settingsService    = $settingsService;
        $this->subeventRepository = $subeventRepository;
    }

    /**
     * Vytvoří formulář.
     * @throws SettingsException
     * @throws Throwable
     */
    public function create() : BaseForm
    {
        $form = $this->baseFormFactory->create();

        /** @var Bs3FormRenderer $renderer */
        $renderer                                   = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addText('seminarName', 'admin.configuration.seminar_name')
            ->addRule(Form::FILLED, 'admin.configuration.seminar_name_empty');

        $seminarFromDate = $form->addDatePicker('seminarFromDate', 'admin.configuration.seminar_from_date')
            ->addRule(Form::FILLED, 'admin.configuration.seminar_from_date_empty');

        $seminarToDate = $form->addDatePicker('seminarToDate', 'admin.configuration.seminar_to_date')
            ->addRule(Form::FILLED, 'admin.configuration.seminar_to_date_empty');

        $editRegistrationTo = $form->addDatePicker('editRegistrationTo', 'admin.configuration.edit_registration_to')
            ->addRule(Form::FILLED, 'admin.configuration.edit_registration_to_empty');

        $seminarFromDate->addRule([$this, 'validateSeminarFromDate'], 'admin.configuration.seminar_from_date_after_to', [$seminarFromDate, $seminarToDate]);
        $seminarToDate->addRule([$this, 'validateSeminarToDate'], 'admin.configuration.seminar_to_date_before_from', [$seminarToDate, $seminarFromDate]);
        $editRegistrationTo->addRule([$this, 'validateEditRegistrationTo'], 'admin.configuration.edit_registration_to_after_from', [$editRegistrationTo, $seminarFromDate]);

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'seminarName' => $this->settingsService->getValue(Settings::SEMINAR_NAME),
            'seminarFromDate' => $this->settingsService->getDateValue(Settings::SEMINAR_FROM_DATE),
            'seminarToDate' => $this->settingsService->getDateValue(Settings::SEMINAR_TO_DATE),
            'editRegistrationTo' => $this->settingsService->getDateValue(Settings::EDIT_REGISTRATION_TO),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws SettingsException
     * @throws Throwable
     */
    public function processForm(BaseForm $form, stdClass $values) : void
    {
        $this->settingsService->setValue(Settings::SEMINAR_NAME, $values->seminarName);
        $implicitSubevent = $this->subeventRepository->findImplicit();
        $implicitSubevent->setName($values->seminarName);
        $this->subeventRepository->save($implicitSubevent);

        $this->settingsService->setDateValue(Settings::SEMINAR_FROM_DATE, $values->seminarFromDate);
        $this->settingsService->setDateValue(Settings::SEMINAR_TO_DATE, $values->seminarToDate);
        $this->settingsService->setDateValue(Settings::EDIT_REGISTRATION_TO, $values->editRegistrationTo);
    }

    /**
     * Ověří, že datum začátku semináře je dříve než konce.
     * @param DateTime[] $args
     */
    public function validateSeminarFromDate(DatePicker $field, array $args) : bool
    {
        return $args[0] <= $args[1];
    }

    /**
     * Ověří, že datum konce semináře je později než začátku.
     * @param DateTime[] $args
     */
    public function validateSeminarToDate(DatePicker $field, array $args) : bool
    {
        return $args[0] >= $args[1];
    }

    /**
     * Ověří, že datum uzavření registrace je dříve než začátek semináře.
     * @param DateTime[] $args
     */
    public function validateEditRegistrationTo(DatePicker $field, array $args) : bool
    {
        return $args[0] < $args[1];
    }
}