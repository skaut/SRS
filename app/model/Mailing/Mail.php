<?php

namespace App\Model\Mailing;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Abstraktní entita e-mail.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="MailRepository")
 * @ORM\Table(name="mail")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "mail_to_user" = "MailToUser",
 *     "mail_to_roles" = "MailToRoles"
 * })
 */
abstract class Mail
{
    /**
     * E-mail zaslaný uživateli.
     */
    const TO_USER = 'user';

    /**
     * E-mail zaslaný rolím.
     */
    const TO_ROLES = 'roles';


    /**
     * Typ e-mailu.
     */
    protected $type;

    use Identifier;

    /**
     * Předmět e-mailu.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $subject;

    /**
     * Datum a čas odeslání.
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $datetime;


    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
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
}
