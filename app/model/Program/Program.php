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
 *
 * @ORM\Entity(repositoryClass="\SRS\Model\Program\ProgramRepository")
 *  @JMS\ExclusionPolicy("none")
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
     * @JMS\Type("SRS\Model\Program\Block")
     *
     */
    protected $block;


    /**
     *  @ORM\ManyToMany(targetEntity="\SRS\model\User", inversedBy="programs", cascade={"persist"})
     * @JMS\Type("ArrayCollection<SRS\Model\User>")
     * @JMS\Exclude
     */
    protected $attendees;

    /**
     * @ORM\Column(type="datetime")
     * @JMS\Type("DateTime")
     */
    protected $start;

    /**
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    protected $duration;

    /**
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     */
    protected $mandatory = false;

    /**
     * @JMS\Type("DateTime")
    */
    protected $end;

    /**
     * @JMS\Type("string")
     */
    protected $title;

    /**
     * @JMS\Type("boolean")
     * @JMS\SerializedName("allDay")
     */
    protected $allDay = false;


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

    public function getEnd() {
        return $this->end;
    }

    public function setEnd($end) {
        $this->end = $end;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }


    public function getAllday() {
        return $this->allDay;
    }

    public function setAllDay($allDay) {
        $this->allDay = $allDay;
    }



}


class ProgramRepository extends \Nella\Doctrine\Repository
{
    public function findAllForJson($basicDuration) {
        $programs = $this->_em->getRepository($this->_entityName)->findAll();

        foreach ($programs as $program) {
            $minutes = $basicDuration*$program->duration;
            $end = $clone = clone $program->start;
            $end->modify("+ {$minutes} minutes");
            //\Nette\Diagnostics\Debugger::dump($end);
            $program->end = $end;

            if ($program->block != null) {
                $program->title = $program->block->name;
            }
            else {
                $program->title = "(Nepřiřazeno)";
            }

        }
        return $programs;

    }

}
