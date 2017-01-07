<?php

namespace App\ApiModule\DTO;

class CalendarConfigDTO
{
    /**
     * @var \DateTime
     */
    private $seminarStart;

    /**
     * @var integer
     */
    private $seminarDuration;

    /**
     * @var integer
     */
    private $year;

    /**
     * @var integer
     */
    private $month;

    /**
     * @var integer
     */
    private $day;

    /**
     * @var boolean
     */
    private $editAllowed;
}