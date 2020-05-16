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
     */
    private string $fromEmail;

    /**
     * Jméno odesílatele.
     */
    private string $fromName;

    /**
     * Příjemci mailu.
     *
     * @var User[]
     */
    private array $recipients;

    /**
     * Kopie mailu.
     */
    private string $copy;

    /**
     * Předmět mailu.
     */
    private string $subject;

    /**
     * Text mailu.
     */
    private string $text;

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
