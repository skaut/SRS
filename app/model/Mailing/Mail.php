<?php

namespace App\Model\Mailing;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
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
    const TO_USER = 'user';
    const TO_ROLES = 'roles';

    protected $type;

    use Identifier;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $subject;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $datetime;


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