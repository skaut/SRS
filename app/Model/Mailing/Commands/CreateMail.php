<?php

declare(strict_types=1);

namespace App\Model\Mailing\Commands;

use App\Model\Acl\Role;
use App\Model\Structure\Subevent;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;

class CreateMail
{
    /**
     * @param Collection<int, User>|null     $recipientUsers
     * @param Collection<int, Role>|null`    $recipientRoles
     * @param Collection<int, Subevent>|null $recipientSubevents
     * @param Collection<int, string>|null   $recipientEmails
     */
    public function __construct(
        private readonly Collection|null $recipientUsers,
        private readonly Collection|null $recipientRoles,
        private readonly Collection|null $recipientSubevents,
        private readonly Collection|null $recipientEmails,
        private readonly string $subject,
        private readonly string $text,
        private readonly bool $automatic = false,
    ) {
    }

    /** @return Collection<int, User>|null */
    public function getRecipientUsers(): Collection|null
    {
        return $this->recipientUsers;
    }

    /** @return Collection<int, Role>|null */
    public function getRecipientRoles(): Collection|null
    {
        return $this->recipientRoles;
    }

    /** @return Collection<int, Subevent>|null */
    public function getRecipientSubevents(): Collection|null
    {
        return $this->recipientSubevents;
    }

    /** @return Collection<int, string>|null */
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
