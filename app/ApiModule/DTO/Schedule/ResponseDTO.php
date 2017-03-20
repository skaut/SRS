<?php

namespace App\ApiModule\DTO\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;


/**
 * Objekt pro přenos stavu do FullCalendar.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ResponseDTO extends Nette\Object
{
    /**
     * @JMS\Type("string")
     * @var string
     */
    private $message;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $status;

    /**
     * @JMS\Type("App\ApiModule\DTO\Schedule\ProgramDetailDTO")
     * @var ProgramDetailDTO
     */
    private $program;


    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getProgram()
    {
        return $this->program;
    }

    /**
     * @param mixed $program
     */
    public function setProgram($program)
    {
        $this->program = $program;
    }
}