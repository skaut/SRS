<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Services\ISettingsService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormsRendering\Renderers\Bs3FormRenderer;
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

    private BaseFormFactory $baseFormFactory;

    private ISettingsService $settingsService;

    private SubeventRepository $subeventRepository;

    public function __construct(
        BaseFormFactory $baseForm,
        ISettingsService $settingsService,
        SubeventRepository $subeventRepository
    ) {
        $this->baseFormFactory    = $baseForm;
        $this->settingsService    = $settingsService;
        $this->subeventRepository = $subeventRepository;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        /** @var Bs3FormRenderer $renderer */
        $renderer                                   = $form->getRenderer();
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
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws SettingsException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values): void
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
