<?php

declare(strict_types=1);

namespace App\Model\Mailing\Commands\Handlers;

use App\Model\Mailing\Commands\CreateMail;
use App\Model\Mailing\Mail;
use App\Model\Mailing\MailQueue;
use App\Model\Mailing\Repositories\MailQueueRepository;
use App\Model\Mailing\Repositories\MailRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

use function array_unique;

class CreateMailHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailRepository $mailRepository,
        private readonly MailQueueRepository $mailQueueRepository,
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
                    $recipients[] = $user->getEmail();
                }
            }

            if ($command->getRecipientRoles() !== null) {
                $mail->setRecipientRoles($command->getRecipientRoles());
                foreach ($command->getRecipientUsers() as $user) { //todo
                    $recipients[] = $user->getEmail();
                }
            }

            if ($command->getRecipientSubevents() !== null) {
                $mail->setRecipientSubevents($command->getRecipientSubevents());
                foreach ($command->getRecipientUsers() as $user) { //todo
                    $recipients[] = $user->getEmail();
                }
            }

            if ($command->getRecipientEmails() !== null) {
                $mail->setRecipientEmails($command->getRecipientEmails()->toArray());
                foreach ($command->getRecipientEmails() as $email) {
                    $recipients[] = $email;
                }
            }

            $mail->setSubject($command->getSubject());
            $mail->setText($command->getText());
            $mail->setDatetime(new DateTimeImmutable());
            $mail->setAutomatic($command->isAutomatic());

            $this->mailRepository->save($mail);

            foreach (array_unique($recipients) as $recipient) {
                $this->mailQueueRepository->save(new MailQueue($recipient, $mail, new DateTimeImmutable()));
            }
        });
    }
}
