<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Cms\Dto\CapacitiesContentDto;
use Nette\Application\UI\Control;

/**
 * Komponenta s kapacitami rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CapacitiesContentControl extends Control
{
    private RoleRepository $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function render(CapacitiesContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/capacities_content.latte');

        $template->heading = $content->getHeading();
        $template->roles   = $this->roleRepository->countUsersInRoles($content->getRoles());

        $template->render();
    }
}
