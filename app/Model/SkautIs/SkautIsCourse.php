<?php

declare(strict_types=1);

namespace App\Model\SkautIs;

use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;

/**
 * Entita skautIS kurz.
 *
 * @ORM\Entity(repositoryClass="SkautIsCourseRepository")
 * @ORM\Table(name="skaut_is_course")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SkautIsCourse
{
    use Id;

    /**
     * SkautIS id kurzu.
     *
     * @ORM\Column(type="integer")
     */
    protected int $skautIsCourseId;

    /**
     * Název kurzu.
     *
     * @ORM\Column(type="string")
     */
    protected string $name;

    public function getId() : int
    {
        return $this->id;
    }

    public function getSkautIsCourseId() : int
    {
        return $this->skautIsCourseId;
    }

    public function setSkautIsCourseId(int $skautIsCourseId) : void
    {
        $this->skautIsCourseId = $skautIsCourseId;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }
}
