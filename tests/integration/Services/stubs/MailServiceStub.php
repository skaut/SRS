<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Acl\Role;
use App\Model\Structure\Subevent;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;

class MailServiceStub implements IMailService
{
    /**
     * @param Collection<int, Role>     $recipientsRoles
     * @param Collection<int, Subevent> $recipientsSubevents
     * @param Collection<int, User>     $recipientsUsers
     * @param Collection<int, string>   $recipientEmails
     */
    public function sendMail(?Collection $recipientsRoles, ?Collection $recipientsSubevents, ?Collection $recipientsUsers, ?Collection $recipientEmails, string $subject, string $text, bool $automatic = false): void
    {
    }

    /**
     * @param Collection<int, User>   $recipientsUsers
     * @param Collection<int, string> $recipientsEmails
     * @param string[]                $parameters
     */
    public function sendMailFromTemplate(?Collection $recipientsUsers, ?Collection $recipientsEmails, string $type, array $parameters): void
    {
    }
}
