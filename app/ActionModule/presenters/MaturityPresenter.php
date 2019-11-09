<?php
declare(strict_types=1);

namespace App\ActionModule\Presenters;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use App\Model\User\ApplicationRepository;
use App\Model\User\RolesApplicationRepository;
use App\Model\User\SubeventsApplicationRepository;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\MailService;
use App\Services\ProgramService;
use App\Utils\Helpers;
use Nette\Application\Responses\TextResponse;
use Throwable;
use Ublaboo\Mailing\Exception\MailingException;
use Ublaboo\Mailing\Exception\MailingMailCreationException;

/**
 * Presenter obsluhující kontrolu splatnosti přihlášek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MaturityPresenter extends ActionBasePresenter
{

	/**
	 * @var ApplicationRepository
	 * @inject
	 */
	public $applicationRepository;

	/**
	 * @var ProgramRepository
	 * @inject
	 */
	public $programRepository;

	/**
	 * @var UserRepository
	 * @inject
	 */
	public $userRepository;

	/**
	 * @var RoleRepository
	 * @inject
	 */
	public $roleRepository;

	/**
	 * @var MailService
	 * @inject
	 */
	public $mailService;

	/**
	 * @var ProgramService
	 * @inject
	 */
	public $programService;

	/**
	 * @var ApplicationService
	 * @inject
	 */
	public $applicationService;

	/**
	 * @var RolesApplicationRepository
	 * @inject
	 */
	public $rolesApplicationRepository;

	/**
	 * @var SettingsFacade
	 * @inject
	 */
	public $settingsFacade;

	/**
	 * @var SubeventsApplicationRepository
	 * @inject
	 */
	public $subeventsApplicationRepository;

	/**
	 * Zruší přihlášky po splatnosti.
	 * @throws SettingsException
	 * @throws Throwable
	 */
	public function actionCancelApplications(): void
	{
		$cancelRegistration = $this->settingsFacade->getIntValue(Settings::CANCEL_REGISTRATION_AFTER_MATURITY);
		if ($cancelRegistration !== null) {
			$cancelRegistrationDate = (new \DateTime())->setTime(0, 0)->modify('-' . $cancelRegistration . ' days');
		} else {
			return;
		}

		foreach ($this->userRepository->findAllWithWaitingForPaymentApplication() as $user) {
			$this->applicationRepository->getEntityManager()->transactional(function ($em) use ($user, $cancelRegistration, $cancelRegistrationDate): void {
				// odhlášení účastníků s nezaplacnou přihláškou rolí
				foreach ($user->getWaitingForPaymentRolesApplications() as $application) {
					$maturityDate = $application->getMaturityDate();

					if ($maturityDate !== null && $cancelRegistrationDate > $maturityDate) {
						$this->applicationService->cancelRegistration($user, ApplicationState::CANCELED_NOT_PAID, null);
						return;
					}
				}

				// zrušení nezaplacených přihlášek podakcí
				$subeventsApplicationCanceled = false;
				foreach ($user->getWaitingForPaymentSubeventsApplications() as $application) {
					$maturityDate = $application->getMaturityDate();

					if ($maturityDate !== null && $cancelRegistrationDate > $maturityDate) {
						$this->applicationService->cancelSubeventsApplication($application, ApplicationState::CANCELED_NOT_PAID, null);
						$subeventsApplicationCanceled = true;
					}
				}

				// pokud účastníkovi nezbyde žádná podakce, je třeba odebrat i roli s cenou podle podakcí, případně jej odhlásit
				if ($subeventsApplicationCanceled && $user->getSubevents()->isEmpty()) {
					$newRoles = $user->getRoles()->filter(function (Role $role) {
						return $role->getFee() !== null;
					});
					if ($newRoles->isEmpty()) {
						$this->applicationService->cancelRegistration($user, ApplicationState::CANCELED_NOT_PAID, null);
					} else {
						$this->applicationService->updateRoles($user, $newRoles, null);
					}
				}
			});
		}

		$response = new TextResponse(null);
		$this->sendResponse($response);
	}

	/**
	 * Rozešle přípomínky splatnosti.
	 * @throws SettingsException
	 * @throws Throwable
	 * @throws MailingException
	 * @throws MailingMailCreationException
	 */
	public function actionSendReminders(): void
	{
		$maturityReminder = $this->settingsFacade->getIntValue(Settings::MATURITY_REMINDER);
		if ($maturityReminder !== null) {
			$maturityReminderDate = (new \DateTime())->setTime(0, 0)->modify('+' . $maturityReminder . ' days');
		} else {
			return;
		}

		foreach ($this->userRepository->findAllWithWaitingForPaymentApplication() as $user) {
			foreach ($user->getWaitingForPaymentApplications() as $application) {
				$maturityDate = $application->getMaturityDate();

				if ($maturityReminderDate == $maturityDate) {
					$this->mailService->sendMailFromTemplate($application->getUser(), '', Template::MATURITY_REMINDER, [
						TemplateVariable::SEMINAR_NAME => $this->settingsFacade->getValue(Settings::SEMINAR_NAME),
						TemplateVariable::APPLICATION_MATURITY => $maturityDate->format(Helpers::DATE_FORMAT),
					]);
				}
			}
		}

		$response = new TextResponse(null);
		$this->sendResponse($response);
	}
}
