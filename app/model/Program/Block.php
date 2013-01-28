<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 26.1.13
 * Time: 13:47
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Model\Program;
use Doctrine\ORM\Mapping as ORM,
    JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity(repositoryClass="\SRS\Model\Program\BlockRepository")
 * @JMS\ExclusionPolicy("none")
 * @property \SRS\Model\User $lector
 * @property \Doctrine\Common\Collections\ArrayCollection $programs
 * @property string $name
 * @property integer $capacity
 * @property string $tools
 * @property string $location
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
     * @ORM\OneToMany(targetEntity="\SRS\Model\Program\Program", mappedBy="block")
     * @JMS\Type("ArrayCollection<SRS\Model\Program\Program>")
     * @JMS\Exclude
     */
    protected $programs;

    /**
     * @ORM\Column
     * @JMS\Exclude
     * @JMS\Type("string")
     */
    protected $name;

    /**
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    protected $capacity;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Type("string")
     */
    protected $tools;

    /**
     * @ORM\Column(nullable=true)
     * @JMS\Type("string")
     */
    protected $location;

    /**
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    protected $duration;

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

    public function setLocation($location)
    {
        $this->location = $location;
    }

    public function getLocation()
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

    public function setPrograms($programs) {
        $this->programs = $programs;
    }

    public function getPrograms() {
        return $this->programs;
    }

}


class BlockRepository extends \Nella\Doctrine\Repository
{

}
