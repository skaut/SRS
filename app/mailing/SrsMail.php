<?php

declare(strict_types=1);

namespace App\Mailing;

use Nette;
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
    public function compose(Nette\Mail\Message $message, ?IMessageData $mailData = null) : void
    {
        $message->setFrom($mailData->getFromEmail(), $mailData->getFromName());

        foreach ($mailData->getRecipients() as $recipient) {
            if (empty($recipient->getEmail())) {
                continue;
            }
            $message->addBcc($recipient->getEmail(), $recipient->getDisplayName());
        }

        if ($mailData->getCopy()) {
            $message->addBcc($mailData->getCopy());
        }

        $message->setSubject($mailData->getSubject());

        $message->setHtmlBody($mailData->getText());
    }
}
