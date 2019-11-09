<?php
declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;

/**
 * Formulář pro nastavení dokladů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PaymentProofForm
{

	use Nette\SmartObject;

	/** @var BaseForm */
	private $baseFormFactory;

	/** @var SettingsFacade */
	private $settingsFacade;

	public function __construct(BaseForm $baseForm, SettingsFacade $settingsFacade)
	{
		$this->baseFormFactory = $baseForm;
		$this->settingsFacade = $settingsFacade;
	}

	/**
	 * Vytvoří formulář.
	 * @throws SettingsException
	 * @throws \Throwable
	 */
	public function create(): Form
	{
		$form = $this->baseFormFactory->create();

		$renderer = $form->getRenderer();
		$renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
		$renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

		$form->addTextArea('company', 'admin.configuration.company')
			->addRule(Form::FILLED, 'admin.configuration.company_empty');

		$form->addText('ico', 'admin.configuration.ico')
			->addRule(Form::FILLED, 'admin.configuration.ico_empty')
			->addRule(Form::PATTERN, 'admin.configuration.ico_format', '^\d{8}$');

		$form->addText('accountant', 'admin.configuration.accountant')
			->addRule(Form::FILLED, 'admin.configuration.accountant_empty');

		$form->addText('printLocation', 'admin.configuration.print_location')
			->addRule(Form::FILLED, 'admin.configuration.print_location_empty');

		$form->addSubmit('submit', 'admin.common.save');

		$form->setDefaults([
			'company' => $this->settingsFacade->getValue(Settings::COMPANY),
			'ico' => $this->settingsFacade->getValue(Settings::ICO),
			'accountant' => $this->settingsFacade->getValue(Settings::ACCOUNTANT),
			'printLocation' => $this->settingsFacadey->getValue(Settings::PRINT_LOCATION),
		]);

		$form->onSuccess[] = [$this, 'processForm'];

		return $form;
	}

	/**
	 * Zpracuje formulář.
	 * @throws SettingsException
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws \Throwable
	 */
	public function processForm(Form $form, \stdClass $values): void
	{
		$this->settingsFacade->setValue(Settings::COMPANY, $values['company']);
		$this->settingsFacade->setValue(Settings::ICO, $values['ico']);
		$this->settingsFacade->setValue(Settings::ACCOUNTANT, $values['accountant']);
		$this->settingsFacade->setValue(Settings::PRINT_LOCATION, $values['printLocation']);
	}
}
