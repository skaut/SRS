<?php

namespace App\Mailing;

use Nette;
use Ublaboo\Mailing\IComposableMail;
use Ublaboo\Mailing\Mail;


/**
 * Třída pro vytváření hromadných e-mailů s libovolným textem.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TextMail extends Mail implements IComposableMail
{
    /**
     * @param  Nette\Mail\Message $message
     * @param  mixed $params
     */
    public function compose(Nette\Mail\Message $message, $params = NULL)
    {
        $message->setFrom($params['fromEmail'], $params['fromName']);

        foreach ($params['recipients'] as $recipient)
            $message->addBcc($recipient->getEmail(), $recipient->getDisplayName());

        if ($params['copy'])
            $message->addBcc($params['copy']);

        $message->setSubject($params['subject']);

        $message->setHtmlBody($params['text']);
    }
}