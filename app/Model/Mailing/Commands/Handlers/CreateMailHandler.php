<?php

declare(strict_types=1);

namespace App\Model\Mailing\Commands\Handlers;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Mailing\Commands\CreateMail;
use App\Model\Mailing\Mail;
use App\Model\Mailing\MailQueue;
use App\Model\Mailing\Recipient;
use App\Model\Mailing\Repositories\MailQueueRepository;
use App\Model\Mailing\Repositories\MailRepository;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Repositories\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

use function array_unique;

use const SORT_REGULAR;

class CreateMailHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailRepository $mailRepository,
        private readonly MailQueueRepository $mailQueueRepository,
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly SubeventRepository $subeventRepository,
    ) {
    }

    public function __invoke(CreateMail $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            $mail       = new Mail();
            $recipients = [];

            if ($command->getRecipientUsers() !== null) {
                $mail->setRecipientUsers($command->getRecipientUsers());
                foreach ($command->getRecipientUsers() as $user) {
                    $recipients[] = Recipient::createFromUser($user);
                }
            }

            if ($command->getRecipientRoles() !== null) {
                $mail->setRecipientRoles($command->getRecipientRoles());
                $rolesIds = $this->roleRepository->findRolesIds($command->getRecipientRoles());
                foreach ($this->userRepository->findAllApprovedInRoles($rolesIds) as $user) {
                    $recipients[] = Recipient::createFromUser($user);
                }
            }

            if ($command->getRecipientSubevents() !== null) {
                $mail->setRecipientSubevents($command->getRecipientSubevents());
                $subeventsIds = $this->subeventRepository->findSubeventsIds($command->getRecipientSubevents());
                foreach ($this->userRepository->findAllWithSubevents($subeventsIds) as $user) {
                    $recipients[] = Recipient::createFromUser($user);
                }
            }

            if ($command->getRecipientEmails() !== null) {
                $mail->setRecipientEmails($command->getRecipientEmails()->toArray());
                foreach ($command->getRecipientEmails() as $email) {
                    $recipients[] = new Recipient($email);
                }
            }

            $mail->setSubject($command->getSubject());
            $mail->setText($command->getText());
            $mail->setDatetime(new DateTimeImmutable());
            $mail->setAutomatic($command->isAutomatic());

            $this->mailRepository->save($mail);

            foreach (array_unique($recipients, SORT_REGULAR) as $recipient) {
                $this->mailQueueRepository->save(new MailQueue($recipient, $mail, new DateTimeImmutable()));
            }
        });
    }
}
