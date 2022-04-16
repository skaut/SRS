<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Mailing\Mail;
use App\Model\Mailing\MailBatch;
use App\Model\Mailing\Repositories\MailRepository;
use App\Model\Program\Commands\SaveBatch;
use App\Model\Program\Commands\SendBatch;
use App\Model\Program\Commands\SendMail;
use App\Services\CommandBus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SendMailHandler implements MessageHandlerInterface
{
    private CommandBus $commandBus;

    private EntityManagerInterface $em;

    private MailRepository $mailRepository;

    public function __construct(CommandBus $commandBus, EntityManagerInterface $em, MailRepository $mailRepository)
    {
        $this->commandBus     = $commandBus;
        $this->em             = $em;
        $this->mailRepository = $mailRepository;
    }

    public function __invoke(SendMail $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            $batch = new MailBatch();
            $this->commandBus->handle(new SaveBatch($batch));

            $mail = new Mail();
            $mail->setBatch($batch);

            if ($command->getRecipientUsers() !== null) {
                $mail->setRecipientUsers($command->getRecipientUsers());
            }

            if ($command->getRecipientRoles() !== null) {
                $mail->setRecipientRoles($command->getRecipientRoles());
            }

            if ($command->getRecipientSubevents() !== null) {
                $mail->setRecipientSubevents($command->getRecipientSubevents());
            }

            if ($command->getRecipientEmails() !== null) {
                $mail->setRecipientEmails($command->getRecipientEmails()->toArray());
            }

            $mail->setSubject($command->getSubject());
            $mail->setText($command->getText());
            $mail->setDatetime(new DateTimeImmutable());
            $mail->setAutomatic(false);

            $this->mailRepository->save($mail);

            $this->commandBus->handle(new SendBatch($batch));
        });
    }
}
