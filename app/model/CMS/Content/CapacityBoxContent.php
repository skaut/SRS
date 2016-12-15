<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="capacity_box_content")
 */
class CapacityBoxContent extends Content
{
    /** @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role") */
    protected $roles;

    public function __construct()
    {
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }
}