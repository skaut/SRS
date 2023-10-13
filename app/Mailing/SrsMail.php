<?php

declare(strict_types=1);

namespace App\Mailing;

use Nette\Mail\Message;
use Ublaboo\Mailing\AbstractMail;
use Ublaboo\Mailing\IComposableMail;
use Ublaboo\Mailing\IMessageData;

/**
 * Třída pro vytváření hromadných e-mailů.
 */
class SrsMail extends AbstractMail implements IComposableMail
{
    /** @param SrsMailData|null $mailData */
    public function compose(Message $message, IMessageData|null $mailData = null): void
    {
        $message->setFrom($mailData->getFrom()->getEmail(), $mailData->getFrom()->getName());
        $message->addTo($mailData->getTo()->getEmail(), $mailData->getTo()->getName());
        $message->setSubject($mailData->getSubject());

        $this->template->subject = $mailData->getSubject();
        $this->template->text    = $mailData->getText();
    }
}
