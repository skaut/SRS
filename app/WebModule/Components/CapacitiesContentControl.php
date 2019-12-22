<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\RoleRepository;
use App\Model\Cms\Content\CapacitiesContentDto;
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
        $this->roleRepository = $roleRepository;
    }

    public function render(CapacitiesContentDto $content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/capacities_content.latte');

        $template->heading = $content->getHeading();
        $template->roles   = $this->roleRepository->countUsersInRoles($content->getRoles());

        $template->render();
    }
}
