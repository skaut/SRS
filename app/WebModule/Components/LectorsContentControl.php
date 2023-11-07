<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Cms\Dto\ContentDto;
use App\Model\User\Repositories\UserRepository;

/**
 * Komponenta obsahu s přehledem lektorů.
 */
class LectorsContentControl extends BaseContentControl
{
    public function __construct(private readonly UserRepository $userRepository, private readonly RoleRepository $roleRepository)
    {
    }

    public function render(ContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/lectors_content.latte');

        $template->heading = $content->getHeading();
        $template->lectors = $this->userRepository->findAllApprovedInRole($this->roleRepository->findBySystemName(Role::LECTOR));

        $template->render();
    }
}
