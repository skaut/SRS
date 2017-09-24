<?php

namespace App\ActionModule\Presenters;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Enums\ApplicationState;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\ApplicationRepository;
use App\Model\User\UserRepository;
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
     * Zkontroluje splatnost přihlášek.
     */
    public function actionCheck()
    {
        foreach ($this->applicationRepository->findWaitingForPaymentApplications() as $application) {
            $maturityDate = $application->getMaturityDate();
            if ($maturityDate === NULL)
                continue;

            $currentDate = (new \DateTime())->modify('-1 day');

            if ($currentDate >= $maturityDate) {
                $application->setState(ApplicationState::CANCELED_NOT_PAID);
                $this->applicationRepository->save($application);

                $this->programRepository->updateUserPrograms($application->getUser());
                $this->userRepository->save($application->getUser());
            }
        }
    }
}
