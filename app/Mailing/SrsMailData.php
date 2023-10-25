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
     * @param string    $senderName Jméno odesilatele mailu.
     * @param Recipient $to       Příjemce mailu.
     * @param string    $subject  Předmět mailu.
     * @param string    $text     Text mailu.
     */
    public function __construct(
        private readonly string    $senderName,
        private readonly Recipient $to,
        private readonly string    $subject,
        private readonly string    $text,
    ) {
    }

    public function getSenderName(): string
    {
        return $this->senderName;
    }

    public function getTo(): Recipient
    {
        return $this->to;
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
