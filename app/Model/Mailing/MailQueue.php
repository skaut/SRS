<?php

declare(strict_types=1);

namespace App\Model\Mailing;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita položka fronty e-mailů.
 */
#[ORM\Entity]
#[ORM\Table(name: 'mail_queue')]
class MailQueue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private int|null $id = null;

    #[ORM\Column(type: 'string')]
    protected string $recipient;

    #[ORM\ManyToOne(targetEntity: Mail::class, cascade: ['persist'])]
    protected Mail $mail;

    #[ORM\Column(type: 'boolean')]
    protected bool $sent = false;

    /**
     * Datum a čas zařazení.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $enqueueDatetime;

    /**
     * Datum a čas odeslání.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $sendDatetime;

    public function __construct(string $recipient, Mail $mail, DateTimeImmutable $enqueueDatetime)
    {
        $this->recipient       = $recipient;
        $this->mail            = $mail;
        $this->enqueueDatetime = $enqueueDatetime;
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function getMail(): Mail
    {
        return $this->mail;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    public function setSent(bool $sent): void
    {
        $this->sent = $sent;
    }

    public function getEnqueueDatetime(): DateTimeImmutable
    {
        return $this->enqueueDatetime;
    }

    public function getSendDatetime(): DateTimeImmutable
    {
        return $this->sendDatetime;
    }

    public function setSendDatetime(DateTimeImmutable $sendDatetime): void
    {
        $this->sendDatetime = $sendDatetime;
    }
}
