<?php

namespace App\WebModule\Components;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\User\UserRepository;
use Nette\Application\UI\Control;


/**
 * Komponenta s přehledem lektorů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class LectorsContentControl extends Control
{
    /** @var UserRepository */
    private $userRepository;

    /** @var RoleRepository */
    private $roleRepository;


    /**
     * UsersContentControl constructor.
     * @param UserRepository $userRepository
     * @param RoleRepository $roleRepository
     */
    public function __construct(UserRepository $userRepository, RoleRepository $roleRepository)
    {
        parent::__construct();

        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    /**
     * @param $content
     */
    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/lectors_content.latte');

        $template->heading = $content->getHeading();
        $template->lectors = $this->userRepository->findAllInRole($this->roleRepository->findBySystemName(Role::LECTOR));

        $template->render();
    }
}
