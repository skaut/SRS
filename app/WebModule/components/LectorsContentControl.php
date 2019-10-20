<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\CMS\Content\ContentDTO;
use App\Model\CMS\Content\LectorsContent;
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
        parent::__construct();

        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    public function render(ContentDTO $content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/lectors_content.latte');

        $template->heading = $content->getHeading();
        $template->lectors = $this->userRepository->findAllApprovedInRole($this->roleRepository->findBySystemName(Role::LECTOR));

        $template->render();
    }
}
