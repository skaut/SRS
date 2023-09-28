<?php

declare(strict_types=1);

namespace App\Services;

use Doctrine\Common\Collections\Collection;

class MailServiceStub implements IMailService
{
    public function sendMail(Collection|null $recipientsRoles, Collection|null $recipientsSubevents, Collection|null $recipientsUsers, Collection|null $recipientEmails, string $subject, string $text, bool $automatic = false): void
    {
    }

    /** @param string[] $parameters */
    public function sendMailFromTemplate(Collection|null $recipientsUsers, Collection|null $recipientsEmails, string $type, array $parameters): void
    {
    }
}
