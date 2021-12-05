<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

use App\Model\User\TicketCheck;

class SaveTicketCheck
{
    private TicketCheck $ticketCheck;

    public function __construct(TicketCheck $ticketCheck)
    {
        $this->ticketCheck = $ticketCheck;
    }

    public function getTicketCheck(): TicketCheck
    {
        return $this->ticketCheck;
    }
}
