<?php

namespace App\WebModule\Components;

use App\Model\ACL\RoleRepository;
use Nette\Application\UI\Control;

class CapacitiesContentControl extends Control
{
    /** @var RoleRepository */
    private $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        parent::__construct();

        $this->roleRepository = $roleRepository;
    }

    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/capacities_content.latte');

        $template->heading = $content->getHeading();
        $template->roles = $this->roleRepository->countApprovedUsersInRoles($content->getRoles());

        $template->render();
    }
}