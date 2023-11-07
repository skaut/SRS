<?php

declare(strict_types=1);

namespace App\Model\Cms;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu se vstupenkou.
 */
#[ORM\Entity]
#[ORM\Table(name: 'ticket_content')]
class TicketContent extends Content implements IContent
{
    protected string $type = Content::TICKET;
}
