<?php

declare(strict_types=1);

namespace App\Services;

use Doctrine\Common\Collections\Collection;

class MailServiceStub implements IMailService {

    function sendMail(?Collection $recipientsRoles, ?Collection $recipientsSubevents, ?Collection $recipientsUsers, ?Collection $recipientEmails, string $subject, string $text, bool $automatic = false): void
    {
    }

    function sendMailFromTemplate(?Collection $recipientsUsers, ?Collection $recipientsEmails, string $type, array $parameters): void
    {
    }
}