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
 * Entita dávka e-mailů.
 *
 * @ORM\Entity
 * @ORM\Table(name="mail_batch")
 */
class MailBatch
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false)
     */
    private ?int $id = null;

    /**
     * E-maily v dávce.
     *
     * @ORM\OneToMany(targetEntity="Mail", mappedBy="batch", cascade={"persist"})
     *
     * @var Collection<int, Mail>
     */
    protected Collection $mails;

    /**
     * Dávka byla odeslána.
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $sent = false;

    public function __construct()
    {
        $this->mails = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMails(): Collection
    {
        return $this->mails;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    public function setSent(bool $sent): void
    {
        $this->sent = $sent;
    }
}
