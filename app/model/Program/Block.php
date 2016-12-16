<?php

namespace App\Model\Program;

use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 * @ORM\Table(name="block")
 * @JMS\ExclusionPolicy("none")
 */
class Block
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User", cascade={"persist"})
     * @var User
     * @JMS\Type("User")
     * @JMS\Exclude
     */
    protected $lector;

    /**
     * @ORM\OneToMany(targetEntity="Program", mappedBy="block", cascade={"persist", "remove"})
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection<Program>")
     * @JMS\Exclude
     */
    protected $programs;

    /**
     * @ORM\Column(type="string")
     * @var string
     * @JMS\Type("string")
     */
    protected $name;

    /**
     * @ORM\Column(type="integer")
     * @var int
     * @JMS\Type("integer")
     */
    protected $capacity;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     * @JMS\Type("string")
     */
    protected $tools;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="blocks", cascade={"persist"})
     * @var Category
     * @JMS\Type("Category")
     * @JMS\Exclude
     */
    protected $category;

    /**
     * @ORM\Column(type="integer")
     * @var int
     * @JMS\Type("integer")
     */
    protected $duration;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $perex;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $description;

    /**
     * Block constructor.
     */
    public function __construct()
    {
        $this->programs = new \Doctrine\Common\Collections\ArrayCollection();
    }
}