<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\ACL\RoleRepository;
use App\Model\CMS\Content\CapacitiesContent;
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


    public function __construct(RoleRepository $roleRepository)
    {
        parent::__construct();

        $this->roleRepository = $roleRepository;
    }

    public function render(CapacitiesContent $content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/capacities_content.latte');

        $template->heading = $content->getHeading();
        $template->roles   = $this->roleRepository->countUsersInRoles($content->getRoles());

        $template->render();
    }
}
