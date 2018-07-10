<?php
declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\ACL\RoleRepository;
use App\Model\User\UserRepository;
use Nette\Application\UI\Control;


/**
 * Komponenta s přehledem uživatelů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class UsersContentControl extends Control
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
        $template->setFile(__DIR__ . '/templates/users_content.latte');

        $template->heading = $content->getHeading();
        $template->users = $this->userRepository->findAllApprovedInRoles(
            $this->roleRepository->findRolesIds($content->getRoles())
        );

        $template->render();
    }
}
