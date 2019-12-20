<?php

declare(strict_types=1);

namespace ApiModule\Dto\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů o lektorovi do FullCalendar.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class LectorDetailDto
{
    use Nette\SmartObject;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $id;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $name;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $about;

    /**
     * @JMS\Type("string")
     * @var ?string
     */
    private $photo;

    public function getId() : int
    {
        return $this->id;
    }

    public function setId(int $id) : void
    {
        $this->id = $id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getAbout() : ?string
    {
        return $this->about;
    }

    public function setAbout(?string $about) : void
    {
        $this->about = $about;
    }

    public function getPhoto() : ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo) : void
    {
        $this->photo = $photo;
    }
}
