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
     * @JMS\Type("string")
     * @var string
     */
    private $seminarToDate;

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

    public function getSeminarToDate() : string
    {
        return $this->seminarToDate;
    }

    public function setSeminarToDate(string $seminarToDate) : void
    {
        $this->seminarToDate = $seminarToDate;
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
