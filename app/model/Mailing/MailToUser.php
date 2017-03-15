<?php

namespace App\Model\Mailing;



use App\Model\User\User;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\Table(name="mail_to_user")
 */
class MailToUser extends Mail
{
    protected $type = Mail::TO_USER;

    /**
     * @ORM\ManyToOne(targetEntity="App\Model\User\User", cascade={"persist"})
     * @var User
     */
    protected $recipientUser;

    /**
     * @return User
     */
    public function getRecipientUser()
    {
        return $this->recipientUser;
    }

    /**
     * @param User $recipientUser
     */
    public function setRecipientUser($recipientUser)
    {
        $this->recipientUser = $recipientUser;
    }
}