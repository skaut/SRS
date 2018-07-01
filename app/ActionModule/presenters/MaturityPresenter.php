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
use App\Model\Settings\SettingsRepository;
use App\Model\User\Application;
use App\Model\User\ApplicationRepository;
use App\Model\User\RolesApplicationRepository;
use App\Model\User\SubeventsApplicationRepository;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\MailService;
use App\Services\ProgramService;
use App\Utils\Helpers;


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
     * @var SettingsRepository
     * @inject
     */
    public $settingsRepository;

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
     * @var SubeventsApplicationRepository
     * @inject
     */
    public $subeventsApplicationRepository;


    /**
     * Zkontroluje splatnost přihlášek.
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     */
    public function actionCheck()
    {
        $cancelRegistration = $this->settingsRepository->getValue(Settings::CANCEL_REGISTRATION_AFTER_MATURITY);
        if ($cancelRegistration !== NULL)
            $cancelRegistrationDate = (new \DateTime())->setTime(0, 0)->modify('-' . $cancelRegistration . ' days');
        else
            $cancelRegistrationDate = NULL;

        $maturityReminder = $this->settingsRepository->getValue(Settings::MATURITY_REMINDER);
        if ($maturityReminder !== NULL)
            $maturityReminderDate = (new \DateTime())->setTime(0, 0)->modify('+' . $maturityReminder . ' days');
        else
            $maturityReminderDate = NULL;

        foreach ($this->userRepository->findAllWithWaitingForPaymentApplication() as $user) {
            $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($user, $cancelRegistration, $cancelRegistrationDate, $maturityReminder, $maturityReminderDate) {
                foreach ($user->getWaitingForPaymentRolesApplications() as $application) {
                    if ($application->getType() == Application::ROLES) {
                        $maturityDate = $application->getMaturityDate();
                        if ($maturityDate === NULL)
                            continue;

                        if ($cancelRegistration !== NULL && $cancelRegistrationDate > $maturityDate) {
                            $rolesWithoutFee = $user->getRoles()->filter(function (Role $role) {
                                return $role->getFee() == 0;
                            });

                            if ($rolesWithoutFee->isEmpty()) {
                                $this->applicationService->cancelRegistration($user, ApplicationState::CANCELED_NOT_PAID, NULL);
                                return;
                            } else {
                                $this->applicationService->updateRoles($user, $rolesWithoutFee, NULL);
                            }
                        }
                    }
                }

                foreach ($user->getWaitingForPaymentSubeventsApplications() as $application) {
                    if ($application->getType() == Application::SUBEVENTS) {
                        $maturityDate = $application->getMaturityDate();
                        if ($maturityDate === NULL)
                            continue;

                        if ($cancelRegistration !== NULL && $cancelRegistrationDate > $maturityDate) {
                            $this->applicationService->cancelSubeventsApplication($application, ApplicationState::CANCELED_NOT_PAID, NULL);
                        }
                    }
                }

                foreach ($user->getWaitingForPaymentApplications() as $application) {
                    $maturityDate = $application->getMaturityDate();
                    if ($maturityDate === NULL)
                        continue;

                    if ($maturityReminder !== NULL && $maturityReminderDate == $maturityDate) {
                        $this->mailService->sendMailFromTemplate($application->getUser(), '', Template::MATURITY_REMINDER, [
                            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
                            TemplateVariable::APPLICATION_MATURITY => $maturityDate->format(Helpers::DATE_FORMAT)
                        ]);
                    }
                }
            });
        }
    }
}
