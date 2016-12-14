<?php

namespace App\Model\Program;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 * @JMS\ExclusionPolicy("none")
 */
class Block
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User")
     * @JMS\Type("App\Model\User\User")
     * @JMS\Exclude
     */
    protected $lector;

    /**
     * @ORM\OneToMany(targetEntity="\App\Model\Program\Program", mappedBy="block", cascade={"persist"}, orphanRemoval=true)
     * @JMS\Type("ArrayCollection<App\Model\Program\Program>")
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
     * @ORM\ManyToOne(targetEntity="\App\Model\Program\Category")
     * @JMS\Type("App\Model\Program\Category")
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
}