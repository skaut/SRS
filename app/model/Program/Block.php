<?php
/**
 * Date: 26.1.13
 * Time: 13:47
 * Author: Michal Májský
 */
namespace SRS\Model\Program;
use Doctrine\ORM\Mapping as ORM,
    JMS\Serializer\Annotation as JMS;

/**
 * Entita programoveho bloku
 *
 * @ORM\Entity(repositoryClass="\SRS\Model\Program\BlockRepository")
 * @JMS\ExclusionPolicy("none")
 * @property \SRS\Model\User $lector
 * @property \Doctrine\Common\Collections\ArrayCollection $programs
 * @property string $name
 * @property integer $capacity
 * @property string $tools
 * @property \SRS\Model\Program\Room $room
 * @property integer $duration
 */
class Block extends \SRS\Model\BaseEntity
{

    /**
     * @ORM\ManyToOne(targetEntity="\SRS\Model\User")
     *
     * @JMS\Type("SRS\Model\User")
     * @JMS\Exclude
     */
    protected $lector;

    /**
     * @ORM\OneToMany(targetEntity="\SRS\Model\Program\Program", mappedBy="block", cascade={"persist"}, orphanRemoval=true)
     * @JMS\Type("ArrayCollection<SRS\Model\Program\Program>")
     * @JMS\Exclude
     */
    protected $programs;

    /**
     * @ORM\Column
     *
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
     * @ORM\ManyToOne(targetEntity="\SRS\Model\Program\Room")
     *
     * @JMS\Type("SRS\Model\Program\Room")
     * @JMS\Exclude
     */
    protected $room;

    /**
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    protected $duration;


    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $perex;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setPerex($perex)
    {
        $this->perex = $perex;
    }

    public function getPerex()
    {
        return $this->perex;
    }

    public function setCapacity($capacity)
    {
        $this->capacity = $capacity;
    }

    public function getCapacity()
    {
        return $this->capacity;
    }

    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setLector($lector)
    {
        $this->lector = $lector;
    }

    public function getLector()
    {
        return $this->lector;
    }

    public function setRoom($location)
    {
        $this->location = $location;
    }

    public function getRoom()
    {
        return $this->location;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setTools($tools)
    {
        $this->tools = $tools;
    }

    public function getTools()
    {
        return $this->tools;
    }

    public function setPrograms($programs)
    {
        $this->programs = $programs;
    }

    public function getPrograms()
    {
        return $this->programs;
    }

}

/**
 * Vlastni repozitar pro praci s bloky
 */
class BlockRepository extends \Nella\Doctrine\Repository
{

}
