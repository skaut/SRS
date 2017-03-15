<?php

namespace App\Model\Mailing;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\Table(name="mail_to_roles")
 */
class MailToRoles extends Mail
{
    protected $type = Mail::TO_ROLES;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role")
     * @var ArrayCollection
     */
    protected $recipientRoles;


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
}