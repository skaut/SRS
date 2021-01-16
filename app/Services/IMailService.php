<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Acl\Role;
use App\Model\Structure\Subevent;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;

interface IMailService
{
    /**
     * Rozešle e-mail.
     *
     * @param Collection<Role>|null     $recipientsRoles
     * @param Collection<Subevent>|null $recipientsSubevents
     * @param Collection<User>|null     $recipientsUsers
     * @param Collection<string>|null   $recipientEmails
     */
    public function sendMail(?Collection $recipientsRoles, ?Collection $recipientsSubevents, ?Collection $recipientsUsers, ?Collection $recipientEmails, string $subject, string $text, bool $automatic = false): void;

    /**
     * Rozešle e-mail podle šablony.
     *
     * @param Collection<User>|null   $recipientsUsers
     * @param Collection<string>|null $recipientsEmails
     * @param string[]                $parameters
     */
    public function sendMailFromTemplate(?Collection $recipientsUsers, ?Collection $recipientsEmails, string $type, array $parameters): void;
}
