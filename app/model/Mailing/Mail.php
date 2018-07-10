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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection
     */
    public function getRecipientRoles()
    {
        return $this->recipientRoles;
    }

    /**
     * @param Collection $recipientRoles
     */
    public function setRecipientRoles($recipientRoles)
    {
        $this->recipientRoles->clear();
        foreach ($recipientRoles as $recipientRole)
            $this->recipientRoles->add($recipientRole);
    }

    /**
     * Vrací příjemce (role) oddělené čárkou.
     * @return string
     */
    public function getRecipientRolesText()
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
    public function getRecipientUsers()
    {
        return $this->recipientUsers;
    }

    /**
     * @param Collection $recipientUsers
     */
    public function setRecipientUsers($recipientUsers)
    {
        $this->recipientUsers->clear();
        foreach ($recipientUsers as $recipientUser)
            $this->recipientUsers->add($recipientUser);
    }

    /**
     * Vrací příjemce (uživatele) oddělené čárkou.
     * @return string
     */
    public function getRecipientUsersText()
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
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return \DateTime
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * @param \DateTime $datetime
     */
    public function setDatetime($datetime)
    {
        $this->datetime = $datetime;
    }

    /**
     * @return bool
     */
    public function isAutomatic()
    {
        return $this->automatic;
    }

    /**
     * @param bool $automatic
     */
    public function setAutomatic($automatic)
    {
        $this->automatic = $automatic;
    }
}
