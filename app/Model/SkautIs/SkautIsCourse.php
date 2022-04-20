<?php

declare(strict_types=1);

namespace App\Model\SkautIs;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita skautIS kurz
 */
#[ORM\Entity]
#[ORM\Table(name: 'skaut_is_course')]
class SkautIsCourse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    /**
     * SkautIS id kurzu
     */
    #[ORM\Column(type: 'integer')]
    protected int $skautIsCourseId;

    /**
     * Název kurzu
     */
    #[ORM\Column(type: 'string')]
    protected string $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSkautIsCourseId(): int
    {
        return $this->skautIsCourseId;
    }

    public function setSkautIsCourseId(int $skautIsCourseId): void
    {
        $this->skautIsCourseId = $skautIsCourseId;
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
