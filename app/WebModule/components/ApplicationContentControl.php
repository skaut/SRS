<?php

namespace App\WebModule\Components;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\User\UserRepository;
use App\Services\Authenticator;
use App\WebModule\Forms\ApplicationForm;
use Nette\Application\UI\Control;
use Nette\Forms\Form;


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


    public function __construct(ApplicationForm $applicationFormFactory, Authenticator $authenticator, UserRepository $userRepository,
                                RoleRepository $roleRepository)
    {
        parent::__construct();

        $this->applicationFormFactory = $applicationFormFactory;
        $this->authenticator = $authenticator;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/application_content.latte');

        $template->heading = $content->getHeading();

        $template->backlink = $this->getPresenter()->getHttpRequest()->getUrl()->getPath();

        $user = $this->getPresenter()->user;
        $template->guestRole = $user->isInRole($this->roleRepository->findBySystemName(Role::GUEST)->getName());
        $template->testRole = Role::TEST;

        if ($user->isLoggedIn()) {
            $template->unapprovedRole = $user->isInRole($this->roleRepository->findBySystemName(Role::UNAPPROVED)->getName());
            $template->nonregisteredRole = $user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED)->getName());
            $template->dbuser = $this->userRepository->findById($user->id);
        }

        $template->render();
    }

    protected function createComponentApplicationForm()
    {
        $form = $this->applicationFormFactory->create($this->getPresenter()->user->id);

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
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