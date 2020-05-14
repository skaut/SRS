<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Schedule;

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

    /** @JMS\Type("int") */
    private int $id;

    /** @JMS\Type("string") */
    private string $name;

    /** @JMS\Type("string") */
    private ?string $about;

    /**
     * @JMS\Type("string")
     */
    private ?string $photo = null;

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
