<?php

declare(strict_types=1);

namespace App\Model\User;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita družina.
 */
#[ORM\Entity]
#[ORM\Table(name: 'patrol')]
class Patrol
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id           = null;

    /**
     * Název družiny.
     */
    #[ORM\Column(type: 'string')]
    protected string $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}