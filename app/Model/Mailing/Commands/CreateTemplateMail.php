<?php

declare(strict_types=1);

namespace App\Model\Mailing\Commands;

use App\Model\User\User;
use Doctrine\Common\Collections\Collection;

class CreateTemplateMail
{
    /**
     * @param Collection<int, User>   $recipientUsers,
     * @param Collection<int, string> $recipientEmails,
     * @param string[]                $parameters
     */
    public function __construct(
        private readonly Collection|null $recipientUsers,
        private readonly Collection|null $recipientEmails,
        private readonly string $template,
        private readonly array $parameters,
    ) {
    }

    /** @return Collection<int, User>|null */
    public function getRecipientUsers(): Collection|null
    {
        return $this->recipientUsers;
    }

    /** @return Collection<int, string>|null */
    public function getRecipientEmails(): Collection|null
    {
        return $this->recipientEmails;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    /** @return string[] */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
