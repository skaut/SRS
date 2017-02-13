<?php

namespace App\Model\Mailing;


use App\Model\ACL\Role;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * @ORM\Entity
 * @ORM\Table(name="mail_to_roles")
 */
class MailToRoles extends Mail
{
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