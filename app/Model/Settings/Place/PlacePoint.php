<?php

declare(strict_types=1);

namespace App\Model\Settings\Place;

use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;

/**
 * Entita mapového bodu.
 *
 * @ORM\Entity(repositoryClass="PlacePointRepository")
 * @ORM\Table(name="place_point")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PlacePoint
{
    use Id;

    /**
     * Název bodu.
     *
     * @ORM\Column(type="string")
     */
    protected string $name;

    /**
     * Zeměpisná šířka.
     *
     * @ORM\Column(type="float")
     */
    protected float $gpsLat;

    /**
     * Zeměpisná délka.
     *
     * @ORM\Column(type="float")
     */
    protected float $gpsLon;

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getGpsLat() : float
    {
        return $this->gpsLat;
    }

    public function setGpsLat(float $gpsLat) : void
    {
        $this->gpsLat = $gpsLat;
    }

    public function getGpsLon() : float
    {
        return $this->gpsLon;
    }

    public function setGpsLon(float $gpsLon) : void
    {
        $this->gpsLon = $gpsLon;
    }
}
