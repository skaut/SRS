<?php

namespace App\Model\Program;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * @ORM\Entity
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
     * Room constructor.
     */
    public function __construct()
    {
        $this->blocks = new ArrayCollection();
    }
}