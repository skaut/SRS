<?php

namespace App\ApiModule\DTO\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;


class CalendarConfigDTO extends Nette\Object
{
    /**
     * @JMS\Type("string")
     * @var string
     */
    private $seminarFromDate;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $seminarDuration;

    /**
     * @JMS\Type("boolean")
     * @var boolean
     */
    private $allowedModifySchedule;


    /**
     * @return string
     */
    public function getSeminarFromDate()
    {
        return $this->seminarFromDate;
    }

    /**
     * @param string $seminarFromDate
     */
    public function setSeminarFromDate($seminarFromDate)
    {
        $this->seminarFromDate = $seminarFromDate;
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