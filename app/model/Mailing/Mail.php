<?php

namespace App\Model\Mailing;


use App\Model\ACL\Role;
use App\Model\User\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="MailRepository")
 * @ORM\Table(name="mail")
 */
class Mail
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $subject;

    /**
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @var User
     */
    protected $toUser;

    /**
     * @ORM\ManyToOne(targetEntity="Role", cascade={"persist"})
     * @var Role
     */
    protected $toRole;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $datetime;
}