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
        foreach ($this->applicationRepository->findWaitingForPaymentApplications() as $application) {
            //kontrola splatnosti
            $maturityDate = $application->getMaturityDate();
            if ($maturityDate === NULL)
                continue;

            $date = (new \DateTime())->setTime(0, 0);

            if ($date > $maturityDate) {
                $this->userRepository->getEntityManager()->transactional(function($em) use($application) {
                    if ($application->isFirst()) {
                        $user = $application->getUser();

                        $user->setRoles(new ArrayCollection([$this->roleRepository->findBySystemName(Role::NONREGISTERED)]));
                        foreach ($user->getApplications() as $application) {
                            $this->applicationRepository->remove($application);
                        }
                        $this->userRepository->save($user);
                    }
                    else {
                        $application->setState(ApplicationState::CANCELED_NOT_PAID);
                        $this->applicationRepository->save($application);
                    }

                    $this->programRepository->updateUserPrograms($application->getUser());
                    $this->userRepository->save($application->getUser());
                });
            }

            //pripominka splatnosti
            $maturityReminder = $this->settingsRepository->getValue(Settings::MATURITY_REMINDER);

            $date = (new \DateTime())->setTime(0, 0)->modify('+' . $maturityReminder . ' days');

            if ($date == $maturityDate) {
                $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$application->getUser()]), '', Template::MATURITY_REMINDER, [
                    TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
                    TemplateVariable::MATURITY => $maturityDate->format('j. n. Y')
                ]);
            }
        }
    }
}
