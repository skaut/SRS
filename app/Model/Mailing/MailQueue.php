<?php

declare(strict_types=1);

namespace App\Model\Mailing;

use App\Model\Acl\Role;
use App\Model\Structure\Subevent;
use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use function implode;

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


    public function getId(): int|null
    {
        return $this->id;
    }
}
