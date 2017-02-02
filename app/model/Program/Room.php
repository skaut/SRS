<?php

namespace App\Model\Program;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * @ORM\Entity(repositoryClass="RoomRepository")
 * @ORM\Table(name="room")
 */
class Room
{
    use Identifier;

    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }


}