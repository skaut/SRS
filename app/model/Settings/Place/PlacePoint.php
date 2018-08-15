<?php
declare(strict_types=1);

namespace App\Model\Settings\Place;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita mapového bodu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="PlacePointRepository")
 * @ORM\Table(name="place_point")
 */
class PlacePoint
{
    use Identifier;

    /**
     * Název bodu.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * Zeměpisná šířka.
     * @ORM\Column(type="float")
     * @var float
     */
    protected $gpsLat;

    /**
     * Zeměpisná délka.
     * @ORM\Column(type="float")
     * @var float
     */
    protected $gpsLon;


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

    /**
     * @return float
     */
    public function getGpsLat(): float
    {
        return $this->gpsLat;
    }

    /**
     * @param float $gpsLat
     */
    public function setGpsLat(float $gpsLat): void
    {
        $this->gpsLat = $gpsLat;
    }

    /**
     * @return float
     */
    public function getGpsLon(): float
    {
        return $this->gpsLon;
    }

    /**
     * @param float $gpsLon
     */
    public function setGpsLon(float $gpsLon): void
    {
        $this->gpsLon = $gpsLon;
    }
}
