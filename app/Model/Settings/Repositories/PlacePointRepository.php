<?php

declare(strict_types=1);

namespace App\Model\Settings\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Settings\PlacePoint;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující mapové body.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class PlacePointRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, PlacePoint::class);
    }

    public function findById(?int $id): ?PlacePoint
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * @throws ORMException
     */
    public function save(PlacePoint $placePoint): void
    {
        $this->em->persist($placePoint);
        $this->em->flush();
    }

    /**
     * @throws ORMException
     */
    public function remove(PlacePoint $placePoint): void
    {
        $this->em->remove($placePoint);
        $this->em->flush();
    }
}
