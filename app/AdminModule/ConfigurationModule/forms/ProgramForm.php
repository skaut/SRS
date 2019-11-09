<?php
declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Enums\ProgramRegistrationType;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Translation\Translator;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use Nextras\Forms\Controls\DateTimePicker;

/**
 * Formulář pro nastavení programu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramForm
{

	use Nette\SmartObject;

	/** @var BaseForm */
	private $baseFormFactory;

	/** @var SettingsFacade */
	private $settingsFacade;

	/** @var Translator */
	private $translator;

	public function __construct(BaseForm $baseForm, SettingsFacade $settingsFacade, Translator $translator)
	{
		$this->baseFormFactory = $baseForm;
		$this->settingsFacade = $settingsFacade;
		$this->translator = $translator;
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

		$registerProgramsTypeSelect = $form->addSelect(
			'registerProgramsType',
			'admin.configuration.register_programs_type',
			$this->prepareRegisterProgramsTypeOptions()
		);
		$registerProgramsTypeSelect
			->addCondition($form::EQUAL, ProgramRegistrationType::ALLOWED_FROM_TO)
			->toggle('register-programs-from')
			->toggle('register-programs-to');

		$registerProgramsFrom = $form->addDateTimePicker('registerProgramsFrom', 'admin.configuration.register_programs_from')
			->setOption('id', 'register-programs-from');

		$registerProgramsTo = $form->addDateTimePicker('registerProgramsTo', 'admin.configuration.register_programs_to')
			->setOption('id', 'register-programs-to');

		$form->addCheckbox('isAllowedRegisterProgramsBeforePayment', 'admin.configuration.is_allowed_register_programs_before_payment');

		$registerProgramsFrom
			->addCondition(Form::FILLED)
			->addRule([$this, 'validateRegisterProgramsFrom'], 'admin.configuration.register_programs_from_after_to', [$registerProgramsFrom, $registerProgramsTo]);

		$registerProgramsTo
			->addCondition(Form::FILLED)
			->addRule([$this, 'validateRegisterProgramsTo'], 'admin.configuration.register_programs_to_before_from', [$registerProgramsTo, $registerProgramsFrom]);

		$form->addCheckbox('isAllowedAddBlock', 'admin.configuration.is_allowed_add_block');
		$form->addCheckbox('isAllowedModifySchedule', 'admin.configuration.is_allowed_modify_schedule');

		$form->addSubmit('submit', 'admin.common.save');

		$form->setDefaults([
			'isAllowedAddBlock' => $this->settingsFacade->getBoolValue(Settings::IS_ALLOWED_ADD_BLOCK),
			'isAllowedModifySchedule' => $this->settingsFacade->getBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE),
			'registerProgramsType' => $this->settingsFacade->getValue(Settings::REGISTER_PROGRAMS_TYPE),
			'registerProgramsFrom' => $this->settingsFacade->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM),
			'registerProgramsTo' => $this->settingsFacade->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO),
			'isAllowedRegisterProgramsBeforePayment' => $this->settingsFacade->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT),
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
		$this->settingsFacade->setBoolValue(Settings::IS_ALLOWED_ADD_BLOCK, $values['isAllowedAddBlock']);
		$this->settingsFacade->setBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE, $values['isAllowedModifySchedule']);
		$this->settingsFacade->setValue(Settings::REGISTER_PROGRAMS_TYPE, $values['registerProgramsType']);
		$this->settingsFacade->setBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, $values['isAllowedRegisterProgramsBeforePayment']);
		$this->settingsFacade->setDateTimeValue(Settings::REGISTER_PROGRAMS_FROM, $values['registerProgramsFrom']);
		$this->settingsFacade->setDateTimeValue(Settings::REGISTER_PROGRAMS_TO, $values['registerProgramsTo']);
	}

	/**
	 * Ověří, že otevření zapisování programů je dříve než uzavření.
	 * @param DateTime[] $args
	 */
	public function validateRegisterProgramsFrom(DateTimePicker $field, array $args): bool
	{
		if ($args[0] === null || $args[1] === null) {
			return true;
		}
		return $args[0] < $args[1];
	}

	/**
	 * Ověří, že uzavření zapisování programů je později než otevření.
	 * @param DateTime[] $args
	 */
	public function validateRegisterProgramsTo(DateTimePicker $field, array $args): bool
	{
		if ($args[0] === null || $args[1] === null) {
			return true;
		}
		return $args[0] > $args[1];
	}

	/**
	 * Vrátí stavy registrace programů.
	 * @return string[]
	 */
	private function prepareRegisterProgramsTypeOptions(): array
	{
		$options = [];
		foreach (ProgramRegistrationType::$types as $type) {
			$options[$type] = 'common.register_programs_type.' . $type;
		}
		return $options;
	}
}
