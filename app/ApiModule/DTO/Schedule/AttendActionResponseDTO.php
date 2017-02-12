<?php

namespace App\ApiModule\DTO\Schedule;


use Nette;
use JMS\Serializer\Annotation as JMS;

class AttendActionResponseDTO extends Nette\Object
{
    private $message;

    private $status;

    private $event;

    private $attendeesCount;


}