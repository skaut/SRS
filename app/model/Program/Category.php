<?php

namespace App\Model\Program;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 * @ORM\Table(name="category")
 */
class Category
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\OneToMany(targetEntity="Block", mappedBy="category", cascade={"persist"})
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection<Block>")
     * @JMS\Exclude
     */
    protected $blocks;

    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", inversedBy="registerableCategories", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $registerableRoles;

    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->blocks = new \Doctrine\Common\Collections\ArrayCollection();
        $this->registerableRoles = new \Doctrine\Common\Collections\ArrayCollection();
    }
}