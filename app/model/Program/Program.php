<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 26.1.13
 * Time: 13:47
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Model\Program;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity(repositoryClass="\SRS\Model\Program\ProgramRepository")
 *
 * @property \SRS\Model\Program\Block $block
 * @property \Doctrine\Common\Collections\ArrayCollection $attendees
 * @property \DateTime $start
 * @property integer $duration
 * @property boolean $mandatory

 */
class Program extends \SRS\Model\BaseEntity
{


    /**
     * @ORM\ManyToOne(targetEntity="\SRS\Model\Program\Block", inversedBy="programs")
     */
    protected $block;


    /**
     *  @ORM\ManyToMany(targetEntity="\SRS\model\User", inversedBy="programs", cascade={"persist"})
     */
    protected $attendees;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $start;

    /**
     * @ORM\Column(type="integer")
     */
    protected $duration;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $mandatory = false;


    public function setAttendees($attendees)
    {
        $this->attendees = $attendees;
    }

    public function getAttendees()
    {
        return $this->attendees;
    }

    public function setBlock($block)
    {
        $this->block = $block;
    }

    public function getBlock()
    {
        return $this->block;
    }

    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
    }

    public function getMandatory()
    {
        return $this->mandatory;
    }

    public function setStart($start)
    {
        $this->start = $start;
    }

    public function getStart()
    {
        return $this->start;
    }

}


class ProgramRepository extends \Nella\Doctrine\Repository
{

}
