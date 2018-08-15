<?php
declare(strict_types=1);

namespace App\Model\Mailing;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita e-mail.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="MailRepository")
 * @ORM\Table(name="mail")
 */
class Mail
{
    use Identifier;

    /**
     * Role, kterým byl e-mail odeslán.
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role")
     * @var Collection
     */
    protected $recipientRoles;

    /**
     * Uživatelé, kterém byl e-mail odeslán.
     * @ORM\ManyToMany(targetEntity="\App\Model\User\User")
     * @var Collection
     */
    protected $recipientUsers;

    /**
     * Předmět e-mailu.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $subject;

    /**
     * Text e-mailu.
     * @ORM\Column(type="text")
     * @var string
     */
    protected $text;

    /**
     * Datum a čas odeslání.
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $datetime;

    /**
     * Automatický e-mail.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $automatic = FALSE;


    /**
     * Mail constructor.
     */
    public function __construct()
    {
        $this->recipientRoles = new ArrayCollection();
        $this->recipientUsers = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Collection
     */
    public function getRecipientRoles(): Collection
    {
        return $this->recipientRoles;
    }

    /**
     * @param Collection $recipientRoles
     */
    public function setRecipientRoles(Collection $recipientRoles): void
    {
        $this->recipientRoles->clear();
        foreach ($recipientRoles as $recipientRole)
            $this->recipientRoles->add($recipientRole);
    }

    /**
     * Vrací příjemce (role) oddělené čárkou.
     * @return string
     */
    public function getRecipientRolesText(): string
    {
        $rolesNames = [];
        foreach ($this->recipientRoles as $role) {
            $rolesNames[] = $role->getName();
        }
        return implode(', ', $rolesNames);
    }

    /**
     * @return Collection
     */
    public function getRecipientUsers(): Collection
    {
        return $this->recipientUsers;
    }

    /**
     * @param Collection $recipientUsers
     */
    public function setRecipientUsers(Collection $recipientUsers): void
    {
        $this->recipientUsers->clear();
        foreach ($recipientUsers as $recipientUser)
            $this->recipientUsers->add($recipientUser);
    }

    /**
     * Vrací příjemce (uživatele) oddělené čárkou.
     * @return string
     */
    public function getRecipientUsersText(): string
    {
        $usersNames = [];
        foreach ($this->recipientUsers as $user) {
            $usersNames[] = $user->getDisplayName();
        }
        return implode(', ', $usersNames);
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /**
     * @return \DateTime
     */
    public function getDatetime(): \DateTime
    {
        return $this->datetime;
    }

    /**
     * @param \DateTime $datetime
     */
    public function setDatetime(\DateTime $datetime): void
    {
        $this->datetime = $datetime;
    }

    /**
     * @return bool
     */
    public function isAutomatic(): bool
    {
        return $this->automatic;
    }

    /**
     * @param bool $automatic
     */
    public function setAutomatic(bool $automatic): void
    {
        $this->automatic = $automatic;
    }
}
