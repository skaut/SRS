<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\CMS\Content\UsersContentDto;
use App\Model\User\UserRepository;
use Nette\Application\UI\Control;

/**
 * Komponenta s přehledem uživatelů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class UsersContentControl extends Control
{
    /** @var UserRepository */
    private $userRepository;


    public function __construct(UserRepository $userRepository)
    {
        parent::__construct();

        $this->userRepository = $userRepository;
    }

    public function render(UsersContentDto $content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/users_content.latte');

        $template->heading = $content->getHeading();
        $template->users   = $this->userRepository->findAllApprovedInRoles($content->getRoles());

        $template->render();
    }
}
