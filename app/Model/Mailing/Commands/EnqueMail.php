<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\User\User;
use Doctrine\Common\Collections\Collection;

class EnqueMail
{
    /** @var Collection<int, User>|null */
    private ?Collection $recipientUsers;

    /** @var Collection<int, string>|null */
    private ?Collection $recipientEmails;

    private string $template;

    /** @var string[] */
    private array $parameters;

    /**
     * @param Collection<int, User>|null   $recipientUsers
     * @param Collection<int, string>|null $recipientEmails
     * @param string[]                     $parameters
     */
    public function __construct(?Collection $recipientUsers, ?Collection $recipientEmails, string $template, array $parameters)
    {
        $this->recipientUsers  = $recipientUsers;
        $this->recipientEmails = $recipientEmails;
        $this->template        = $template;
        $this->parameters      = $parameters;
    }

    /**
     * @return Collection<int, User>|null
     */
    public function getRecipientUsers(): ?Collection
    {
        return $this->recipientUsers;
    }

    /**
     * @return Collection<int, string>|null
     */
    public function getRecipientEmails(): ?Collection
    {
        return $this->recipientEmails;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @return string[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
