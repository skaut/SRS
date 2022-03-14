<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Acl\Role;
use App\Model\Structure\Subevent;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;

class SendMail
{
    private ?Collection $recipientsUsers;

    private ?Collection $recipientsRoles;

    private ?Collection $recipientsSubevents;

    private ?Collection $recipientEmails;

    private string $subject;

    private string $text;

    /**
     * @param Collection<int, User>|null     $recipientsUsers
     * @param Collection<int, Role>|null     $recipientsRoles
     * @param Collection<int, Subevent>|null $recipientsSubevents
     * @param Collection<int, string>|null   $recipientEmails
     */
    public function __construct(?Collection $recipientsUsers, ?Collection $recipientsRoles, ?Collection $recipientsSubevents, ?Collection $recipientEmails, string $subject, string $text)
    {
        $this->recipientsUsers     = $recipientsUsers;
        $this->recipientsRoles     = $recipientsRoles;
        $this->recipientsSubevents = $recipientsSubevents;
        $this->recipientEmails     = $recipientEmails;
        $this->subject             = $subject;
        $this->text                = $text;
    }

    /**
     * @return Collection<int, User>|null
     */
    public function getRecipientsUsers(): ?Collection
    {
        return $this->recipientsUsers;
    }

    /**
     * @return Collection<int, Role>|null
     */
    public function getRecipientsRoles(): ?Collection
    {
        return $this->recipientsRoles;
    }

    /**
     * @return Collection<int, Subevent>|null
     */
    public function getRecipientsSubevents(): ?Collection
    {
        return $this->recipientsSubevents;
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
