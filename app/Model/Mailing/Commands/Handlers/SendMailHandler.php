<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Mailing\Mail;
use App\Model\Mailing\Repositories\MailRepository;
use App\Model\Program\Commands\SendMail;
use App\Model\Program\Commands\SendQueue;
use App\Services\CommandBus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SendMailHandler implements MessageHandlerInterface
{
    private CommandBus $commandBus;

    private MailRepository $mailRepository;

    public function __construct(CommandBus $commandBus, MailRepository $mailRepository)
    {
        $this->commandBus = $commandBus;
        $this->mailRepository = $mailRepository;
    }

    public function __invoke(SendMail $command): void
    {
        $mail = new Mail();

        if ($command->getRecipientUsers() !== null) {
            $mail->setRecipientUsers($command->getRecipientUsers());
        }

        if ($command->getRecipientRoles() !== null) {
            $mail->setRecipientRoles($command->getRecipientRoles());
        }

        if ($command->getRecipientRoles() !== null) {
            $mail->setRecipientRoles($command->getRecipientRoles());
        }

        if ($command->getRecipientEmails() !== null) {
            $mail->setRecipientEmails($command->getRecipientEmails());
        }

        $mail->setSubject($command->getSubject());
        $mail->setText($command->getText());
        $mail->setDatetime(new DateTimeImmutable());
        $mail->setAutomatic(false);

        $this->mailRepository->save($mail);

        $this->commandBus->handle(new SendQueue());
    }
}
