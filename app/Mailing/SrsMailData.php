<?php

declare(strict_types=1);

namespace App\Mailing;

use App\Model\Mailing\Recipient;
use Ublaboo\Mailing\IMessageData;

/**
 * Třída pro parametry hromadného e-mailu.
 */
class SrsMailData implements IMessageData
{
    /**
     * @param Recipient[] $recipients
     */
    public function __construct(
        /**
         * Odesilatel mailu.
         */
        private Recipient $from,
        /**
         * Příjemci mailu.
         */
        private array $recipients,
        /**
         * Předmět mailu.
         */
        private string $subject,
        /**
         * Text mailu.
         */
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
