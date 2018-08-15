<?php
declare(strict_types=1);

namespace App\Model\Settings\Place;

use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující mapové body.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PlacePointRepository extends EntityRepository
{
    /**
     * @param $id
     * @return PlacePoint|null
     */
    public function findById(int $id): ?PlacePoint
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param PlacePoint $placePoint
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(PlacePoint $placePoint): void
    {
        $this->_em->persist($placePoint);
        $this->_em->flush();
    }

    /**
     * @param PlacePoint $placePoint
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(PlacePoint $placePoint): void
    {
        $this->_em->remove($placePoint);
        $this->_em->flush();
    }
}
