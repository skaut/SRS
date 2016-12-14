<?php

namespace App\Model\Program;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 */
class Category
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\OneToMany(targetEntity="\App\Model\Program\Block", mappedBy="room", cascade={"persist"}, onDelete="SET NULL")
     * @JMS\Type("ArrayCollection<App\Model\Program\Block>")
     * @JMS\Exclude
     */
    protected $blocks;

    /** @ORM\Column(type="string", unique=true) */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", inversedBy="registerableCategories", cascade={"persist"})
     */
    protected $registerableRoles;
}