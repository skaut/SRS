<?php

namespace App\ApiModule\DTO\Schedule;


use Nette;
use JMS\Serializer\Annotation as JMS;

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
     * @JMS\Type("int")
     * @var int
     */
    private $eventId;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $intData;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $stringData;

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
     * @return int
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @param int $eventId
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;
    }

    /**
     * @return int
     */
    public function getIntData()
    {
        return $this->intData;
    }

    /**
     * @param int $intData
     */
    public function setIntData($intData)
    {
        $this->intData = $intData;
    }

    /**
     * @return string
     */
    public function getStringData()
    {
        return $this->stringData;
    }

    /**
     * @param string $stringData
     */
    public function setStringData($stringData)
    {
        $this->stringData = $stringData;
    }
}