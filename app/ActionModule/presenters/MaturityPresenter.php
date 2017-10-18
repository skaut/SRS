<?php

namespace App\ActionModule\Presenters;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\ApplicationRepository;
use App\Model\User\UserRepository;
use App\Services\MailService;
use Doctrine\Common\Collections\ArrayCollection;
use Nette\Application\Responses\TextResponse;


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
     * Zkontroluje splatnost přihlášek.
     */
    public function actionCheck()
    {
        $cancelRegistration = $this->settingsRepository->getValue(Settings::CANCEL_REGISTRATION_AFTER_MATURITY);
        if ($cancelRegistration !== NULL)
            $cancelRegistrationDate = (new \DateTime())->setTime(0, 0)->modify('-' . $cancelRegistration . ' days');

        $maturityReminder = $this->settingsRepository->getValue(Settings::MATURITY_REMINDER);
        if ($maturityReminder !== NULL)
            $maturityReminderDate = (new \DateTime())->setTime(0, 0)->modify('+' . $maturityReminder . ' days');

        foreach ($this->applicationRepository->findWaitingForPaymentApplications() as $application) {
            $maturityDate = $application->getMaturityDate();
            if ($maturityDate === NULL)
                continue;

            //zrušení registrace
            if ($cancelRegistration !== NULL && $cancelRegistrationDate > $maturityDate) {
                $this->userRepository->getEntityManager()->transactional(function($em) use($application) {
                    if ($application->isFirst()) {
                        $user = $application->getUser();

                        $user->setRoles(new ArrayCollection([$this->roleRepository->findBySystemName(Role::NONREGISTERED)]));
                        $user->setApproved(TRUE);
                        foreach ($user->getApplications() as $application) {
                            $this->applicationRepository->remove($application);
                        }
                        $this->userRepository->save($user);

                        $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$user]), '', Template::REGISTRATION_CANCELED, [
                            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME)
                        ]);
                    }
                    else {
                        $application->setState(ApplicationState::CANCELED_NOT_PAID);
                        $this->applicationRepository->save($application);
                    }

                    $this->programRepository->updateUserPrograms($application->getUser());
                    $this->userRepository->save($application->getUser());
                });
                continue;
            }

            //připomenutí splatnosti
            if ($maturityReminder !== NULL && $maturityReminderDate == $maturityDate) {
                $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$application->getUser()]), '', Template::MATURITY_REMINDER, [
                    TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
                    TemplateVariable::APPLICATION_MATURITY => $maturityDate->format('j. n. Y')
                ]);
            }
        }
    }
}
