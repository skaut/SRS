<?php

declare(strict_types=1);

namespace App\Mailing;

use Nette;
use Ublaboo\Mailing\AbstractMail;
use Ublaboo\Mailing\IComposableMail;
use Ublaboo\Mailing\IMessageData;

/**
 * Třída pro vytváření hromadných e-mailů s libovolným textem.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TextMail extends AbstractMail implements IComposableMail
{
    public function compose(Nette\Mail\Message $message, ?IMessageData $params = null) : void
    {
        $message->setFrom($params['fromEmail'], $params['fromName']);

        foreach ($params['recipients'] as $recipient) {
            if (empty($recipient->getEmail())) {
                continue;
            }
            $message->addBcc($recipient->getEmail(), $recipient->getDisplayName());
        }

        if ($params['copy']) {
            $message->addBcc($params['copy']);
        }

        $message->setSubject($params['subject']);

        $message->setHtmlBody($params['text']);
    }
}
