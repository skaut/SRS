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
use Nettrine\ORM\Entity\Attributes\Id;
use function implode;

/**
 * Entita e-mail.
 *
 * @ORM\Entity(repositoryClass="MailRepository")
 * @ORM\Table(name="mail")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Mail
{
    use Id;

    /**
     * Role, kterým byl e-mail odeslán.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Acl\Role")
     *
     * @var Collection|Role[]
     */
    protected $recipientRoles;

    /**
     * Podakce, jejichž účastníkům byl e-mail odeslán.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Structure\Subevent")
     *
     * @var Collection|Subevent[]
     */
    protected $recipientSubevents;

    /**
     * Uživatelé, kterém byl e-mail odeslán.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\User\User")
     *
     * @var Collection|User[]
     */
    protected $recipientUsers;

    /**
     * Předmět e-mailu.
     *
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $subject;

    /**
     * Text e-mailu.
     *
     * @ORM\Column(type="text")
     *
     * @var string
     */
    protected $text;

    /**
     * Datum a čas odeslání.
     *
     * @ORM\Column(type="datetime_immutable")
     *
     * @var DateTimeImmutable
     */
    protected $datetime;

    /**
     * Automatický e-mail.
     *
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    protected $automatic = false;

    public function __construct()
    {
        $this->recipientRoles     = new ArrayCollection();
        $this->recipientSubevents = new ArrayCollection();
        $this->recipientUsers     = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return Collection|Role[]
     */
    public function getRecipientRoles() : Collection
    {
        return $this->recipientRoles;
    }

    /**
     * @param Collection|Role[] $recipientRoles
     */
    public function setRecipientRoles(Collection $recipientRoles) : void
    {
        $this->recipientRoles->clear();
        foreach ($recipientRoles as $recipientRole) {
            $this->recipientRoles->add($recipientRole);
        }
    }

    /**
     * Vrací příjemce (role) oddělené čárkou.
     */
    public function getRecipientRolesText() : string
    {
        return implode(', ', $this->recipientRoles->map(static function (Role $role) {
            return $role->getName();
        })->toArray());
    }

    /**
     * @return Collection|Subevent[]
     */
    public function getRecipientSubevents() : Collection
    {
        return $this->recipientSubevents;
    }

    /**
     * @param Collection|Subevent[] $recipientSubevents
     */
    public function setRecipientSubevents(Collection $recipientSubevents) : void
    {
        $this->recipientSubevents->clear();
        foreach ($recipientSubevents as $recipientSubevent) {
            $this->recipientSubevents->add($recipientSubevent);
        }
    }

    /**
     * Vrací příjemce (podakce) oddělené čárkou.
     */
    public function getRecipientSubeventsText() : string
    {
        return implode(', ', $this->recipientSubevents->map(static function (Subevent $subevent) {
            return $subevent->getName();
        })->toArray());
    }

    /**
     * @return Collection|User[]
     */
    public function getRecipientUsers() : Collection
    {
        return $this->recipientUsers;
    }

    /**
     * @param Collection|User[] $recipientUsers
     */
    public function setRecipientUsers(Collection $recipientUsers) : void
    {
        $this->recipientUsers->clear();
        foreach ($recipientUsers as $recipientUser) {
            $this->recipientUsers->add($recipientUser);
        }
    }

    /**
     * Vrací příjemce (uživatele) oddělené čárkou.
     */
    public function getRecipientUsersText() : string
    {
        return implode(', ', $this->recipientUsers->map(static function (User $user) {
            return $user->getDisplayName();
        })->toArray());
    }

    public function getSubject() : string
    {
        return $this->subject;
    }

    public function setSubject(string $subject) : void
    {
        $this->subject = $subject;
    }

    public function getText() : string
    {
        return $this->text;
    }

    public function setText(string $text) : void
    {
        $this->text = $text;
    }

    public function getDatetime() : DateTimeImmutable
    {
        return $this->datetime;
    }

    public function setDatetime(DateTimeImmutable $datetime) : void
    {
        $this->datetime = $datetime;
    }

    public function isAutomatic() : bool
    {
        return $this->automatic;
    }

    public function setAutomatic(bool $automatic) : void
    {
        $this->automatic = $automatic;
    }
}
