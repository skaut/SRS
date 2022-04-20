<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos stavu do FullCalendar
 */
class ResponseDto
{
    use Nette\SmartObject;

    /** @JMS\Type("string") */
    private string $message;

    /** @JMS\Type("string") */
    private string $status;

    /** @JMS\Type("App\ApiModule\Dto\Schedule\ProgramDetailDto") */
    private ?ProgramDetailDto $program = null;

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getProgram(): ?ProgramDetailDto
    {
        return $this->program;
    }

    public function setProgram(ProgramDetailDto $program): void
    {
        $this->program = $program;
    }
}
