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
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Ublaboo\Mailing\MailFactory;

use function sleep;

class SendMailsHandler implements MessageHandlerInterface
{
    /** Limit počtu e-mailů odeslaných v rámci jednoho volání. */
    private const BATCH_LIMIT = 100;

    /** Počet e-mailů, po jejichž odeslání se čeká. */
    private const BATCH_WAIT_LIMIT = 10;

    /** Počet sekund čekání před odesláním dalších e-mailů. */
    private const BATCH_WAIT_SECONDS = 2;

    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly MailQueueRepository $mailQueueRepository,
        private readonly MailFactory $mailFactory,
    ) {
    }

    public function __invoke(SendMails $command): void
    {
        $mailsToSend = $this->mailQueueRepository->findMailsToSend(self::BATCH_LIMIT);

        if ($mailsToSend->isEmpty()) {
            return;
        }

        $senderName = $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME));

        $i = 0;
        foreach ($mailsToSend as $mailToSend) {
            $to          = new Recipient($mailToSend->getRecipientEmail(), $mailToSend->getRecipientName());
            $messageData = new SrsMailData($senderName, $to, $mailToSend->getMail()->getSubject(), $mailToSend->getMail()->getText());
            $mail        = $this->mailFactory->createByType(SrsMail::class, $messageData);
            $mail->send();

            $mailToSend->setSent(true);
            $mailToSend->setSendDatetime(new DateTimeImmutable());
            $this->mailQueueRepository->save($mailToSend);

            $i++;
            if ($i % self::BATCH_WAIT_LIMIT === 0) {
                sleep(self::BATCH_WAIT_SECONDS);
            }
        }
    }
}
