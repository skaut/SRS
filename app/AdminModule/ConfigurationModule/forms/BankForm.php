<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use App\Services\BankService;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;

/**
 * Formulár pro nastavení párování plateb.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BankForm
{
    use Nette\SmartObject;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var BankService */
    private $bankService;


    public function __construct(
        BaseForm $baseForm,
        SettingsRepository $settingsRepository,
        BankService $bankService
    ) {
        $this->baseFormFactory    = $baseForm;
        $this->settingsRepository = $settingsRepository;
        $this->bankService        = $bankService;
    }

    /**
     * Vytvoří formulář.
     * @throws SettingsException
     * @throws \Throwable
     */
    public function create() : Form
    {
        $form = $this->baseFormFactory->create();

        $renderer                                   = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addSelect('bank', 'admin.configuration.payment.bank', ['fio' => 'FIO']);
        $form->addText('bankToken', 'admin.configuration.payment.bank_token')
            ->addRule(Form::FILLED, 'admin.configuration.payment.bank_token_empty')
            ->addRule(Form::LENGTH, 'admin.configuration.payment.bank_token_length', 64);
        $form->addDatePicker('bankDownloadFrom', 'admin.configuration.payment.bank_download_from')
            ->addRule(Form::FILLED, 'admin.configuration.payment.bank_download_from_empty');

        $form->addSubmit('submit', 'admin.common.save');

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @throws SettingsException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Throwable
     */
    public function processForm(Form $form, \stdClass $values) : void
    {
        $this->settingsRepository->setValue(Settings::BANK_TOKEN, $values['bankToken']);
        $this->settingsRepository->setDateValue(Settings::BANK_DOWNLOAD_FROM, $values['bankDownloadFrom']);
    }
}
