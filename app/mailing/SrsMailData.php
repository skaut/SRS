<?php

declare(strict_types=1);

namespace App\Mailing;

use App\Model\User\User;
use Ublaboo\Mailing\IMessageData;

/**
 * Třída pro parametry hromadného e-mailu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SrsMailData implements IMessageData
{
    /**
     * E-mail odesílatele.
     *
     * @var string
     */
    private $fromEmail;

    /**
     * Jméno odesílatele.
     *
     * @var string
     */
    private $fromName;

    /**
     * Příjemci mailu.
     *
     * @var User[]
     */
    private $recipients;

    /**
     * Kopie mailu.
     *
     * @var string
     */
    private $copy;

    /**
     * Předmět mailu.
     *
     * @var string
     */
    private $subject;

    /**
     * Text mailu.
     *
     * @var string
     */
    private $text;

    /**
     * @param User[] $recipients
     */
    public function __construct(string $fromEmail, string $fromName, array $recipients, string $copy, string $subject, string $text)
    {
        $this->fromEmail  = $fromEmail;
        $this->fromName   = $fromName;
        $this->recipients = $recipients;
        $this->copy       = $copy;
        $this->subject    = $subject;
        $this->text       = $text;
    }

    public function getFromEmail() : string
    {
        return $this->fromEmail;
    }

    public function getFromName() : string
    {
        return $this->fromName;
    }

    /**
     * @return User[]
     */
    public function getRecipients() : array
    {
        return $this->recipients;
    }

    public function getCopy() : string
    {
        return $this->copy;
    }

    public function getSubject() : string
    {
        return $this->subject;
    }

    public function getText() : string
    {
        return $this->text;
    }
}
