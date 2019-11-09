<?php
declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\MailService;
use Nette;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Form;
use Ublaboo\Mailing\Exception\MailingException;
use Ublaboo\Mailing\Exception\MailingMailCreationException;
use function md5;
use function mt_rand;
use function substr;
use function uniqid;

/**
 * Formulář pro nastavení mailingu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailingForm
{

	use Nette\SmartObject;

	/**
	 * Přihlášený uživatel.
	 * @var User
	 */
	private $user;

	/** @var BaseForm */
	private $baseFormFactory;

	/** @var SettingsFacade */
	private $settingsFacade;

	/** @var UserRepository */
	private $userRepository;

	/** @var MailService */
	private $mailService;

	/** @var LinkGenerator */
	private $linkGenerator;

	public function __construct(
		BaseForm $baseForm,
		SettingsFacade $settingsFacade,
		UserRepository $userRepository,
		MailService $mailService,
		LinkGenerator $linkGenerator
	)
	{
		$this->baseFormFactory = $baseForm;
		$this->settingsFacade = $settingsFacade;
		$this->userRepository = $userRepository;
		$this->mailService = $mailService;
		$this->linkGenerator = $linkGenerator;
	}

	/**
	 * Vytvoří formulář.
	 * @throws SettingsException
	 * @throws \Throwable
	 */
	public function create(int $id): Form
	{
		$this->user = $this->userRepository->findById($id);

		$form = $this->baseFormFactory->create();

		$renderer = $form->getRenderer();
		$renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
		$renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

		$form->addText('seminarEmail', 'admin.configuration.mailing_email')
			->addRule(Form::FILLED, 'admin.configuration.mailing_email_empty')
			->addRule(Form::EMAIL, 'admin.configuration.mailing_email_format');

		$form->addSubmit('submit', 'admin.common.save');

		$form->setDefaults([
			'seminarEmail' => $this->settingsFacade->getValue(Settings::SEMINAR_EMAIL),
		]);

		$form->onSuccess[] = [$this, 'processForm'];

		return $form;
	}

	/**
	 * Zpracuje formulář.
	 * @throws Nette\Application\UI\InvalidLinkException
	 * @throws SettingsException
	 * @throws \Throwable
	 * @throws MailingException
	 * @throws MailingMailCreationException
	 */
	public function processForm(Form $form, \stdClass $values): void
	{
		if ($this->settingsFacade->getValue(Settings::SEMINAR_EMAIL) === $values['seminarEmail']) {
			return;
		}

		$this->settingsFacade->setValue(Settings::SEMINAR_EMAIL_UNVERIFIED, $values['seminarEmail']);

		$verificationCode = substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
		$this->settingsFacade->setValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE, $verificationCode);

		$link = $this->linkGenerator->link('Action:Mailing:verify', ['code' => $verificationCode]);

		$this->mailService->sendMailFromTemplate(
			null,
			$values['seminarEmail'],
			Template::EMAIL_VERIFICATION,
			[
				TemplateVariable::SEMINAR_NAME => $this->settingsFacade->getValue(Settings::SEMINAR_NAME),
				TemplateVariable::EMAIL_VERIFICATION_LINK => $link,
			],
			true
		);
	}
}
