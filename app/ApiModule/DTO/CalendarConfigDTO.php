<?php

namespace App\ApiModule\DTO;


use Nette;
use JMS\Serializer\Annotation as JMS;

class CalendarConfigDTO extends Nette\Object
{
    /**
     * @JMS\Type("int")
     * @var int
     */
    private $seminarStartDay;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $seminarDuration;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $basicBlockDuration;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $year;

    /**
     * @JMS\Type("int")
     * @var integer
     */
    private $month;

    /**
     * @JMS\Type("int")
     * @var integer
     */
    private $day;

    /**
     * @JMS\Type("bool")
     * @var bool
     */
    private $modifyAllowed;

    /**
     * @return int
     */
    public function getSeminarStartDay()
    {
        return $this->seminarStartDay;
    }

    /**
     * @param int $seminarStartDay
     */
    public function setSeminarStartDay($seminarStartDay)
    {
        $this->seminarStartDay = $seminarStartDay;
    }

    /**
     * @return int
     */
    public function getSeminarDuration()
    {
        return $this->seminarDuration;
    }

    /**
     * @param int $seminarDuration
     */
    public function setSeminarDuration($seminarDuration)
    {
        $this->seminarDuration = $seminarDuration;
    }

    /**
     * @return int
     */
    public function getBasicBlockDuration()
    {
        return $this->basicBlockDuration;
    }

    /**
     * @param int $basicBlockDuration
     */
    public function setBasicBlockDuration($basicBlockDuration)
    {
        $this->basicBlockDuration = $basicBlockDuration;
    }

    /**
     * @return int
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param int $year
     */
    public function setYear($year)
    {
        $this->year = $year;
    }

    /**
     * @return int
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * @param int $month
     */
    public function setMonth($month)
    {
        $this->month = $month;
    }

    /**
     * @return int
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * @param int $day
     */
    public function setDay($day)
    {
        $this->day = $day;
    }

    /**
     * @return bool
     */
    public function isModifyAllowed()
    {
        return $this->modifyAllowed;
    }

    /**
     * @param bool $modifyAllowed
     */
    public function setModifyAllowed($modifyAllowed)
    {
        $this->modifyAllowed = $modifyAllowed;
    }
}