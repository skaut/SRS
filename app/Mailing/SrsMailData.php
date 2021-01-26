<?php

declare(strict_types=1);

namespace App\Mailing;

use App\Model\Mailing\Recipient;
use Ublaboo\Mailing\IMessageData;

/**
 * Třída pro parametry hromadného e-mailu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SrsMailData implements IMessageData
{
    /**
     * Odesilatel mailu.
     */
    private Recipient $from;

    /**
     * Příjemci mailu.
     *
     * @var Recipient[]
     */
    private array $recipients;

    /**
     * Předmět mailu.
     */
    private string $subject;

    /**
     * Text mailu.
     */
    private string $text;

    /**
     * @param Recipient[] $recipients
     */
    public function __construct(Recipient $from, array $recipients, string $subject, string $text)
    {
        $this->from       = $from;
        $this->recipients = $recipients;
        $this->subject    = $subject;
        $this->text       = $text;
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
