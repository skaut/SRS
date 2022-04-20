<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Nette;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use stdClass;
use Throwable;

use function assert;

/**
 * Formulář pro nastavení dokladů
 */
class PaymentProofFormFactory
{
    use Nette\SmartObject;

    public function __construct(private BaseFormFactory $baseFormFactory, private CommandBus $commandBus, private QueryBus $queryBus)
    {
    }

    /**
     * Vytvoří formulář
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

        $form->addTextArea('company', 'admin.configuration.payment.payment_proofs.company')
            ->addRule(Form::FILLED, 'admin.configuration.payment.payment_proofs.company_empty');

        $form->addText('ico', 'admin.configuration.payment.payment_proofs.ico')
            ->addRule(Form::FILLED, 'admin.configuration.payment.payment_proofs.ico_empty')
            ->addRule(Form::PATTERN, 'admin.configuration.payment.payment_proofs.ico_format', '^\d{8}$');

        $form->addText('accountant', 'admin.configuration.payment.payment_proofs.accountant')
            ->addRule(Form::FILLED, 'admin.configuration.payment.payment_proofs.accountant_empty');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'company' => $this->queryBus->handle(new SettingStringValueQuery(Settings::COMPANY)),
            'ico' => $this->queryBus->handle(new SettingStringValueQuery(Settings::ICO)),
            'accountant' => $this->queryBus->handle(new SettingStringValueQuery(Settings::ACCOUNTANT)),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář
     *
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values): void
    {
        $this->commandBus->handle(new SetSettingStringValue(Settings::COMPANY, $values->company));
        $this->commandBus->handle(new SetSettingStringValue(Settings::ICO, $values->ico));
        $this->commandBus->handle(new SetSettingStringValue(Settings::ACCOUNTANT, $values->accountant));
    }
}
