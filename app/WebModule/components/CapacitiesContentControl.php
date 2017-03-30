<?php

namespace App\WebModule\Components;

use App\Model\ACL\RoleRepository;
use Nette\Application\UI\Control;


/**
 * Komponenta s kapacitami rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CapacitiesContentControl extends Control
{
    /** @var RoleRepository */
    private $roleRepository;


    /**
     * CapacitiesContentControl constructor.
     * @param RoleRepository $roleRepository
     */
    public function __construct(RoleRepository $roleRepository)
    {
        parent::__construct();

        $this->roleRepository = $roleRepository;
    }

    /**
     * @param $content
     */
    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/capacities_content.latte');

        $template->heading = $content->getHeading();
        $template->roles = $this->roleRepository->countApprovedUsersInRoles($content->getRoles());

        $template->render();
    }
}
