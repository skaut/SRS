<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos konfigurace do FullCalendar.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CalendarConfigDto
{
    use Nette\SmartObject;

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
     * @var bool
     */
    private $allowedModifySchedule;

    public function getSeminarFromDate() : string
    {
        return $this->seminarFromDate;
    }

    public function setSeminarFromDate(string $seminarFromDate) : void
    {
        $this->seminarFromDate = $seminarFromDate;
    }

    public function getSeminarDuration() : int
    {
        return $this->seminarDuration;
    }

    public function setSeminarDuration(int $seminarDuration) : void
    {
        $this->seminarDuration = $seminarDuration;
    }

    public function isAllowedModifySchedule() : bool
    {
        return $this->allowedModifySchedule;
    }

    public function setAllowedModifySchedule(bool $allowedModifySchedule) : void
    {
        $this->allowedModifySchedule = $allowedModifySchedule;
    }
}
