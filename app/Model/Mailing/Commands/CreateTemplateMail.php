<?php

declare(strict_types=1);

namespace App\Model\Mailing\Commands;

use App\Model\User\User;
use Doctrine\Common\Collections\Collection;

class CreateTemplateMail
{
    public function __construct(
        private readonly Collection|null $recipientUsers,
        private readonly Collection|null $recipientEmails,
        private readonly string $template,
        private readonly array $parameters,
    ) {
    }

    /** @return User[]|null */
    public function getRecipientUsers(): Collection|null
    {
        return $this->recipientUsers;
    }

    /** @return string[]|null */
    public function getRecipientEmails(): Collection|null
    {
        return $this->recipientEmails;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
