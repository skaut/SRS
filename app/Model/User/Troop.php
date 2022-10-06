<?php

declare(strict_types=1);

namespace App\Model\User;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita oddíl.
 */
#[ORM\Entity]
#[ORM\Table(name: 'troop')]
class Troop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id           = null;

    /**
     * Název oddílu.
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