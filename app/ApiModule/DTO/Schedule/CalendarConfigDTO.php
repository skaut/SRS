<?php

namespace App\ApiModule\DTO\Schedule;


use Nette;
use JMS\Serializer\Annotation as JMS;

class CalendarConfigDTO extends Nette\Object
{
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
    private $seminarFromYear;

    /**
     * @JMS\Type("int")
     * @var integer
     */
    private $seminarFromMonth;

    /**
     * @JMS\Type("int")
     * @var integer
     */
    private $seminarFromDay;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $seminarFromWeekDay;

    /**
     * @JMS\Type("boolean")
     * @var boolean
     */
    private $allowedModifySchedule;

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
    public function getSeminarFromYear()
    {
        return $this->seminarFromYear;
    }

    /**
     * @param int $seminarFromYear
     */
    public function setSeminarFromYear($seminarFromYear)
    {
        $this->seminarFromYear = $seminarFromYear;
    }

    /**
     * @return int
     */
    public function getSeminarFromMonth()
    {
        return $this->seminarFromMonth;
    }

    /**
     * @param int $seminarFromMonth
     */
    public function setSeminarFromMonth($seminarFromMonth)
    {
        $this->seminarFromMonth = $seminarFromMonth;
    }

    /**
     * @return int
     */
    public function getSeminarFromDay()
    {
        return $this->seminarFromDay;
    }

    /**
     * @param int $seminarFromDay
     */
    public function setSeminarFromDay($seminarFromDay)
    {
        $this->seminarFromDay = $seminarFromDay;
    }

    /**
     * @return int
     */
    public function getSeminarFromWeekDay()
    {
        return $this->seminarFromWeekDay;
    }

    /**
     * @param int $seminarFromWeekDay
     */
    public function setSeminarFromWeekDay($seminarFromWeekDay)
    {
        $this->seminarFromWeekDay = $seminarFromWeekDay;
    }

    /**
     * @return bool
     */
    public function isAllowedModifySchedule()
    {
        return $this->allowedModifySchedule;
    }

    /**
     * @param bool $allowedModifySchedule
     */
    public function setAllowedModifySchedule($allowedModifySchedule)
    {
        $this->allowedModifySchedule = $allowedModifySchedule;
    }
}