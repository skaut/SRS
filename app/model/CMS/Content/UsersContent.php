<?php

namespace App\Model\CMS\Content;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users_content")
 */
class UsersContent extends Content
{
    protected $type = Content::USERS;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role")
     * @var ArrayCollection
     */
    protected $roles;

    /**
     * UserBoxContent constructor.
     */
    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }
}