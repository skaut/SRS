<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Role;
use App\Model\Acl\RoleRepository;
use App\Model\Cms\Content\ContentDto;
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

    public function __construct(UserRepository $userRepository, RoleRepository $roleRepository)
    {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    public function render(ContentDto $content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/lectors_content.latte');

        $template->heading = $content->getHeading();
        $template->lectors = $this->userRepository->findAllApprovedInRole($this->roleRepository->findBySystemName(Role::LECTOR));

        $template->render();
    }
}
