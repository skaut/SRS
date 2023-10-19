<?php

declare(strict_types=1);

namespace App\Model\Mailing\Commands\Handlers;

use App\Mailing\SrsMail;
use App\Mailing\SrsMailData;
use App\Model\Mailing\Commands\SendMails;
use App\Model\Mailing\Recipient;
use App\Model\Mailing\Repositories\MailQueueRepository;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\QueryBus;
use DateTimeImmutable;
use Ublaboo\Mailing\MailFactory;

use function sleep;

class SendMailsHandler
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly MailQueueRepository $mailQueueRepository,
        private readonly MailFactory $mailFactory,
    ) {
    }

    public function __invoke(SendMails $command): void
    {
        $mailsToSend = $this->mailQueueRepository->findMailsToSend(50);

        if ($mailsToSend->isEmpty()) {
            return;
        }

        $from = new Recipient(
            $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_EMAIL)),
            $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME)),
        );

        $i = 0;
        foreach ($mailsToSend as $mailToSend) {
            $to          = new Recipient($mailToSend->getRecipientEmail(), $mailToSend->getRecipientName());
            $messageData = new SrsMailData($from, $to, $mailToSend->getMail()->getSubject(), $mailToSend->getMail()->getText());
            $mail        = $this->mailFactory->createByType(SrsMail::class, $messageData);
            $mail->send();

            $mailToSend->setSent(true);
            $mailToSend->setSendDatetime(new DateTimeImmutable());
            $this->mailQueueRepository->save($mailToSend);

            $i++;
            if ($i % 10 === 0) {
                sleep(2);
            }
        }
    }
}
