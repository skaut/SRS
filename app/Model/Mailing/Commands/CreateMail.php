<?php

declare(strict_types=1);

namespace App\Model\Mailing\Commands;

use Doctrine\Common\Collections\Collection;

class CreateMail
{
    public function __construct(
        private readonly Collection|null $recipientRoles,
        private readonly Collection|null $recipientSubevents,
        private readonly Collection|null $recipientUsers,
        private readonly Collection|null $recipientEmails,
        private readonly string $subject,
        private readonly string $text,
        private readonly bool $automatic = false,
    ) {
    }

    public function getRecipientRoles(): Collection|null
    {
        return $this->recipientRoles;
    }

    public function getRecipientSubevents(): Collection|null
    {
        return $this->recipientSubevents;
    }

    public function getRecipientUsers(): Collection|null
    {
        return $this->recipientUsers;
    }

    public function getRecipientEmails(): Collection|null
    {
        return $this->recipientEmails;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function isAutomatic(): bool
    {
        return $this->automatic;
    }
}
