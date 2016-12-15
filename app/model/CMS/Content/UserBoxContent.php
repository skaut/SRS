<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_box_content")
 */
class UserBoxContent extends Content
{
    /** @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role") */
    protected $roles;

    public function __construct()
    {
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }
}