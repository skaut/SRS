<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos stavu do FullCalendar.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ResponseDto
{
    use Nette\SmartObject;

    /** @JMS\Type("string") */
    private string $message;

    /** @JMS\Type("string") */
    private string $status;

    /** @JMS\Type("App\ApiModule\Dto\Schedule\ProgramDetailDto") */
    private ProgramDetailDto $program;

    public function getMessage() : string
    {
        return $this->message;
    }

    public function setMessage(string $message) : void
    {
        $this->message = $message;
    }

    public function getStatus() : string
    {
        return $this->status;
    }

    public function setStatus(string $status) : void
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
    public function setProgram($program) : void
    {
        $this->program = $program;
    }
}
