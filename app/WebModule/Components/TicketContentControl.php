<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Role;
use App\Model\Cms\Dto\ContentDto;
use App\Services\AclService;

/**
 * Komponenta obsahu se vstupenkou.
 */
class TicketContentControl extends BaseContentControl
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly ITicketControlFactory $ticketControlFactory,
    ) {
    }

    public function render(ContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/ticket_content.latte');

        $template->heading = $content->getHeading();

        $presenter = $this->getPresenter();

        $template->backlink = $presenter->getHttpRequest()->getUrl()->getPath();

        $template->guestRole = $presenter->getUser()->isInRole($this->aclService->findRoleNameBySystemName(Role::GUEST));

        $template->render();
    }

    public function createComponentTicket(): TicketControl
    {
        return $this->ticketControlFactory->create();
    }
}
