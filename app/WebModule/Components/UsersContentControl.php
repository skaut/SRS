<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Dto\UsersContentDto;
use App\Model\User\Repositories\UserRepository;

/**
 * Komponenta obsahu s přehledem uživatelů.
 */
class UsersContentControl extends BaseContentControl
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function render(UsersContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/users_content.latte');

        $template->heading = $content->getHeading();
        $template->users   = $this->userRepository->findAllApprovedInRoles($content->getRoles());

        $template->render();
    }
}
