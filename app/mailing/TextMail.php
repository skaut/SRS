<?php

namespace App\Mailing;


use Nette;
use Ublaboo\Mailing\IComposableMail;
use Ublaboo\Mailing\Mail;

class TextMail extends Mail implements IComposableMail
{
    /**
     * @param  Nette\Mail\Message $message
     * @param  mixed $params
     * @return mixed
     */
    public function compose(Nette\Mail\Message $message, $params = NULL)
    {
        $message->setFrom($params['from']);

        foreach ($params['recipients'] as $recipient)
            $message->addBcc($recipient->getEmail(), $recipient->getDisplayName());

        $message->setSubject($params['subject']);

        $message->setHtmlBody($params['text']);
    }
}