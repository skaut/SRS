<?php

namespace App\ActionModule\Presenters;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
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
            $maturityDate = $application->getMaturityDate();
            if ($maturityDate === NULL)
                continue;

            $date = (new \DateTime())->setTime(0, 0);

            if ($date > $maturityDate) {
                $application->setState(ApplicationState::CANCELED_NOT_PAID);
                $this->applicationRepository->save($application);

                $this->programRepository->updateUserPrograms($application->getUser());
                $this->userRepository->save($application->getUser());
            }


            $maturityReminder = $this->settingsRepository->getValue(Settings::MATURITY_REMINDER);

            $date = (new \DateTime())->setTime(0, 0)->modify('+' . $maturityReminder . ' days');

            if ($date == $maturityDate) {
                $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$this->user]), '', Template::MATURITY_REMINDER, [
                    TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
                    TemplateVariable::MATURITY => $maturityDate
                ]);
            }
        }
    }
}
