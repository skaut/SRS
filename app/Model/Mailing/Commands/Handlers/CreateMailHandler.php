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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

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
            $recipients = new ArrayCollection();

            if ($command->getRecipientUsers() !== null) {
                $mail->setRecipientUsers($command->getRecipientUsers());
                foreach ($command->getRecipientUsers() as $user) {
                    $this->addRecipient($recipients, Recipient::createFromUser($user));
                }
            }

            if ($command->getRecipientRoles() !== null) {
                $mail->setRecipientRoles($command->getRecipientRoles());
                $rolesIds = $this->roleRepository->findRolesIds($command->getRecipientRoles());
                foreach ($this->userRepository->findAllApprovedInRoles($rolesIds) as $user) {
                    $this->addRecipient($recipients, Recipient::createFromUser($user));
                }
            }

            if ($command->getRecipientSubevents() !== null) {
                $mail->setRecipientSubevents($command->getRecipientSubevents());
                $subeventsIds = $this->subeventRepository->findSubeventsIds($command->getRecipientSubevents());
                foreach ($this->userRepository->findAllWithSubevents($subeventsIds) as $user) {
                    $this->addRecipient($recipients, Recipient::createFromUser($user));
                }
            }

            if ($command->getRecipientEmails() !== null) {
                $mail->setRecipientEmails($command->getRecipientEmails()->toArray());
                foreach ($command->getRecipientEmails() as $email) {
                    $this->addRecipient($recipients, new Recipient($email));
                }
            }

            $mail->setSubject($command->getSubject());
            $mail->setText($command->getText());
            $mail->setDatetime(new DateTimeImmutable());
            $mail->setAutomatic($command->isAutomatic());

            $this->mailRepository->save($mail);

            foreach ($recipients as $recipient) {
                $this->mailQueueRepository->save(new MailQueue($recipient, $mail, new DateTimeImmutable()));
            }
        });
    }

    /** @param Collection<int, Recipient> $recipients */
    private function addRecipient(Collection $recipients, Recipient $recipient): void
    {
        if ($recipient->isValid() && ! $recipients->exists(static fn (int $i, Recipient $r) => $r->getEmail() === $recipient->getEmail())) {
            $recipients->add($recipient);
        }
    }
}
