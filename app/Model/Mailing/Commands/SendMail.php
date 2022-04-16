<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Acl\Role;
use App\Model\Structure\Subevent;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;

class SendMail
{
    /** @var Collection<int, User>|null */
    private ?Collection $recipientUsers;

    /** @var Collection<int, Role>|null */
    private ?Collection $recipientRoles;

    /** @var Collection<int, Subevent>|null */
    private ?Collection $recipientSubevents;

    /** @var Collection<int, string>|null */
    private ?Collection $recipientEmails;

    private string $subject;

    private string $text;

    /**
     * @param Collection<int, User>|null     $recipientUsers
     * @param Collection<int, Role>|null     $recipientRoles
     * @param Collection<int, Subevent>|null $recipientSubevents
     * @param Collection<int, string>|null   $recipientEmails
     */
    public function __construct(
        ?Collection $recipientUsers,
        ?Collection $recipientRoles,
        ?Collection $recipientSubevents,
        ?Collection $recipientEmails,
        string $subject,
        string $text
    ) {
        $this->recipientUsers     = $recipientUsers;
        $this->recipientRoles     = $recipientRoles;
        $this->recipientSubevents = $recipientSubevents;
        $this->recipientEmails    = $recipientEmails;
        $this->subject            = $subject;
        $this->text               = $text;
    }

    /**
     * @return Collection<int, User>|null
     */
    public function getRecipientUsers(): ?Collection
    {
        return $this->recipientUsers;
    }

    /**
     * @return Collection<int, Role>|null
     */
    public function getRecipientRoles(): ?Collection
    {
        return $this->recipientRoles;
    }

    /**
     * @return Collection<int, Subevent>|null
     */
    public function getRecipientSubevents(): ?Collection
    {
        return $this->recipientSubevents;
    }

    /**
     * @return Collection<int, string>|null
     */
    public function getRecipientEmails(): ?Collection
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
}
