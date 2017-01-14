<?php

namespace App\Model\Program;

use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="block")
 */
class Block
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User", cascade={"persist"})
     * @var User
     */
    protected $lector;

    /**
     * @ORM\OneToMany(targetEntity="Program", mappedBy="block", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $programs;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $capacity;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $tools;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="blocks", cascade={"persist"})
     * @var Category
     */
    protected $category;

    /**
     * @ORM\Column(type="integer")
     * @var int
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