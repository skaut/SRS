<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos konfigurace do FullCalendar
 */
class CalendarConfigDto
{
    use Nette\SmartObject;

    #[JMS\Type(values: 'string')]
    private string $seminarFromDate;

    #[JMS\Type(values: 'string')]
    private string $seminarToDate;

    #[JMS\Type(values: 'string')]
    private string $minTime;

    #[JMS\Type(values: 'string')]
    private string $maxTime;

    #[JMS\Type(values: 'boolean')]
    private bool $allowedModifySchedule;

    #[JMS\Type(values: 'string')]
    private string $initialView;

    public function getSeminarFromDate(): string
    {
        return $this->seminarFromDate;
    }

    public function setSeminarFromDate(string $seminarFromDate): void
    {
        $this->seminarFromDate = $seminarFromDate;
    }

    public function getSeminarToDate(): string
    {
        return $this->seminarToDate;
    }

    public function setSeminarToDate(string $seminarToDate): void
    {
        $this->seminarToDate = $seminarToDate;
    }

    public function getMinTime(): string
    {
        return $this->minTime;
    }

    public function setMinTime(string $minTime): void
    {
        $this->minTime = $minTime;
    }

    public function getMaxTime(): string
    {
        return $this->maxTime;
    }

    public function setMaxTime(string $maxTime): void
    {
        $this->maxTime = $maxTime;
    }

    public function isAllowedModifySchedule(): bool
    {
        return $this->allowedModifySchedule;
    }

    public function setAllowedModifySchedule(bool $allowedModifySchedule): void
    {
        $this->allowedModifySchedule = $allowedModifySchedule;
    }

    public function getInitialView(): string
    {
        return $this->initialView;
    }

    public function setInitialView(string $initialView): void
    {
        $this->initialView = $initialView;
    }
}
