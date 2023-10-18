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
     * @param Recipient $from    Odesilatel mailu.
     * @param Recipient $to      Příjemce mailu.
     * @param string    $subject Předmět mailu.
     * @param string    $text    Text mailu.
     */
    public function __construct(
        private readonly Recipient $from,
        private readonly Recipient $to,
        private readonly string $subject,
        private readonly string $text,
    ) {
    }

    public function getFrom(): Recipient
    {
        return $this->from;
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
