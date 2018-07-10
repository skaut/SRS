<?php
declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\UserRepository;
use App\Services\Authenticator;
use App\WebModule\Forms\ApplicationForm;
use Nette\Application\UI\Control;
use Nette\Forms\Form;


/**
 * Komponenta s přihláškou.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationContentControl extends Control
{
    /** @var ApplicationForm */
    private $applicationFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var Authenticator */
    private $authenticator;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var SubeventRepository */
    private $subeventRepository;


    /**
     * ApplicationContentControl constructor.
     * @param ApplicationForm $applicationFormFactory
     * @param Authenticator $authenticator
     * @param UserRepository $userRepository
     * @param RoleRepository $roleRepository
     * @param SettingsRepository $settingsRepository
     * @param SubeventRepository $subeventRepository
     */
    public function __construct(ApplicationForm $applicationFormFactory, Authenticator $authenticator,
                                UserRepository $userRepository, RoleRepository $roleRepository,
                                SettingsRepository $settingsRepository, SubeventRepository $subeventRepository)
    {
        parent::__construct();

        $this->applicationFormFactory = $applicationFormFactory;
        $this->authenticator = $authenticator;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->settingsRepository = $settingsRepository;
        $this->subeventRepository = $subeventRepository;
    }

    /**
     * @param $content
     * @throws \App\Model\Settings\SettingsException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Throwable
     */
    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/application_content.latte');

        $template->heading = $content->getHeading();

        $template->backlink = $this->getPresenter()->getHttpRequest()->getUrl()->getPath();

        $user = $this->getPresenter()->user;
        $template->guestRole = $user->isInRole($this->roleRepository->findBySystemName(Role::GUEST)->getName());
        $template->testRole = Role::TEST;

        $explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();

        if ($user->isLoggedIn()) {
            $dbuser = $this->userRepository->findById($user->id);
            $userHasFixedFeeRole = $dbuser->hasFixedFeeRole();

            $template->unapprovedRole = $user->isInRole($this->roleRepository->findBySystemName(Role::UNAPPROVED)->getName());
            $template->nonregisteredRole = $user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED)->getName());
            $template->bankAccount = $this->settingsRepository->getValue(Settings::ACCOUNT_NUMBER);
            $template->dbuser = $dbuser;
            $template->userHasFixedFeeRole = $userHasFixedFeeRole;

            $template->usersApplications = $explicitSubeventsExists && $userHasFixedFeeRole
                ? $dbuser->getNotCanceledApplications()
                : ($explicitSubeventsExists
                    ? $dbuser->getNotCanceledSubeventsApplications()
                    : $dbuser->getNotCanceledRolesApplications()
                );
        }

        $template->explicitSubeventsExists = $explicitSubeventsExists;
        $template->rolesWithSubevents = json_encode($this->roleRepository->findRolesIds($this->roleRepository->findAllWithSubevents()));

        $template->render();
    }

    /**
     * @return \Nette\Application\UI\Form
     * @throws \App\Model\Settings\SettingsException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function createComponentApplicationForm()
    {
        $form = $this->applicationFormFactory->create($this->getPresenter()->user->id);

        $form->onSuccess[] = function (Form $form, array $values) {
            $this->getPresenter()->flashMessage('web.application_content.register_successful', 'success');

            $this->authenticator->updateRoles($this->getPresenter()->user);

            $this->getPresenter()->redirect('this');
        };

        $this->applicationFormFactory->onSkautIsError[] = function () {
            $this->getPresenter()->flashMessage('web.application_content.register_synchronization_failed', 'danger');
        };

        return $form;
    }
}
