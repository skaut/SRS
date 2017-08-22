<?php

namespace App\Model\Mailing;

use Doctrine\Common\Collections\ArrayCollection;
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
     * @var ArrayCollection
     */
    protected $recipientRoles;

    /**
     * Uživatelé, kterém byl e-mail odeslán.
     * @ORM\ManyToMany(targetEntity="App\Model\User\User")
     * @var ArrayCollection
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ArrayCollection
     */
    public function getRecipientRoles()
    {
        return $this->recipientRoles;
    }

    /**
     * @param ArrayCollection $recipientRoles
     */
    public function setRecipientRoles($recipientRoles)
    {
        $this->recipientRoles = $recipientRoles;
    }

    /**
     * @return ArrayCollection
     */
    public function getRecipientUsers()
    {
        return $this->recipientUsers;
    }

    /**
     * @param ArrayCollection $recipientUsers
     */
    public function setRecipientUsers($recipientUsers)
    {
        $this->recipientUsers = $recipientUsers;
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
