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
     * @param Collection<int, Role>|null     $recipientsRoles
     * @param Collection<int, Subevent>|null $recipientsSubevents
     * @param Collection<int, User>|null     $recipientsUsers
     * @param Collection<int, string>|null   $recipientEmails
     */
    public function sendMail(Collection|null $recipientsRoles, Collection|null $recipientsSubevents, Collection|null $recipientsUsers, Collection|null $recipientEmails, string $subject, string $text, bool $automatic = false): void;

    /**
     * Rozešle e-mail podle šablony.
     *
     * @param Collection<int, User>|null   $recipientsUsers
     * @param Collection<int, string>|null $recipientsEmails
     * @param string[]                     $parameters
     */
    public function sendMailFromTemplate(Collection|null $recipientsUsers, Collection|null $recipientsEmails, string $type, array $parameters): void;
}
