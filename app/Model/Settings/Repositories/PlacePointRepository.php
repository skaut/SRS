<?php

declare(strict_types=1);

namespace App\Model\Settings\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Settings\PlacePoint;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující mapové body.
 */
class PlacePointRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, PlacePoint::class);
    }

    /** @return Collection<int, PlacePoint> */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    public function findById(int|null $id): PlacePoint|null
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    public function save(PlacePoint $placePoint): void
    {
        $this->em->persist($placePoint);
        $this->em->flush();
    }

    public function remove(PlacePoint $placePoint): void
    {
        $this->em->remove($placePoint);
        $this->em->flush();
    }
}
