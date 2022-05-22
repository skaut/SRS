<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Cms\Dto\CapacitiesContentDto;

/**
 * Komponenta s kapacitami rolí.
 */
class CapacitiesContentControl extends BaseContentControl
{
    public function __construct(private RoleRepository $roleRepository)
    {
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
