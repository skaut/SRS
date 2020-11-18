<?php

declare(strict_types=1);

namespace App\Mailing;

use Nette\Mail\Message;
use Ublaboo\Mailing\AbstractMail;
use Ublaboo\Mailing\IComposableMail;
use Ublaboo\Mailing\IMessageData;

/**
 * Třída pro vytváření hromadných e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SrsMail extends AbstractMail implements IComposableMail
{
    /**
     * @param SrsMailData|null $mailData
     */
    public function compose(Message $message, ?IMessageData $mailData = null) : void
    {
        $message->setFrom($mailData->getFrom()->getEmail(), $mailData->getFrom()->getName());

        foreach ($mailData->getRecipients() as $recipient) {
            if (! empty($recipient->getEmail())) {
                $message->addBcc($recipient->getEmail(), $recipient->getName());
            }
        }

        $message->setSubject($mailData->getSubject());

        $message->setHtmlBody($mailData->getText());
    }
}
