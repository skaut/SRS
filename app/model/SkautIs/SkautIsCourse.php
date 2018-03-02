<?php
declare(strict_types=1);

namespace App\Model\SkautIs;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita skautIS kurz.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="SkautIsCourseRepository")
 * @ORM\Table(name="skaut_is_course")
 */
class SkautIsCourse
{
    use Identifier;

    /**
     * SkautIS id kurzu.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $skautIsCourseId;

    /**
     * Název kurzu.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getSkautIsCourseId(): int
    {
        return $this->skautIsCourseId;
    }

    /**
     * @param int $skautIsCourseId
     */
    public function setSkautIsCourseId(int $skautIsCourseId): void
    {
        $this->skautIsCourseId = $skautIsCourseId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
