<?php

namespace App\ApiModule\DTO;

class ProgramDTO
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $start;

    /**
     * @var integer
     */
    private $duration;

    /**
     * @var string
     */
    private $perex;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $room;

    /**
     * @var string
     */
    private $category;

    /**
     * @var string
     */
    private $lector;

    /**
     * @var string
     */
    private $aboutLector;

    /**
     * @var integer
     */
    private $attendeesCount;

    /**
     * @var integer
     */
    private $capacity;

    /**
     * @var boolean
     */
    private $attends;

    /**
     * @var boolean
     */
    private $blocked;

    /**
     * @var array
     */
    private $blocksPrograms;
}