<?php

namespace App\Model\Mailing;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;


/**
 * Entita e-mail zaslaný rolím.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="mail_to_roles")
 */
class MailToRoles extends Mail
{
    protected $type = Mail::TO_ROLES;

    /**
     * Role, kterým byl e-mail odeslán.
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