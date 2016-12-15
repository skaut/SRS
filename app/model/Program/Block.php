<?php

namespace App\Model\Program;

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
     * @JMS\Type("App\Model\User\User")
     * @JMS\Exclude
     */
    protected $lector;

    /**
     * @ORM\OneToMany(targetEntity="Program", mappedBy="block", cascade={"persist", "remove"})
     * @JMS\Type("ArrayCollection<Program>")
     * @JMS\Exclude
     */
    protected $programs;

    /**
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    protected $name;

    /**
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    protected $capacity;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    protected $tools;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="blocks", cascade={"persist"})
     * @JMS\Type("Category")
     * @JMS\Exclude
     */
    protected $category;

    /**
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    protected $duration;

    /** @ORM\Column(type="text", nullable=true) */
    protected $perex;

    /** @ORM\Column(type="text", nullable=true) */
    protected $description;

    public function __construct()
    {
        $this->programs = new \Doctrine\Common\Collections\ArrayCollection();
    }
}