<?php

declare(strict_types=1);

namespace App\Mailing;

use App\Model\Mailing\Recipient;
use Ublaboo\Mailing\IMessageData;

/**
 * Třída pro parametry hromadného e-mailu
 */
class SrsMailData implements IMessageData
{
    /**
     * @param Recipient   $from       Odesilatel mailu
     * @param Recipient[] $recipients Příjemci mailu
     * @param string      $subject    Předmět mailu
     * @param string      $text       Text mailu
     */
    public function __construct(
        private Recipient $from,
        private array $recipients,
        private string $subject,
        private string $text
    ) {
    }

    public function getFrom(): Recipient
    {
        return $this->from;
    }

    /**
     * @return Recipient[]
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
