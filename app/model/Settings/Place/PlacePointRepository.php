<?php

declare(strict_types=1);

namespace App\Model\Settings\Place;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\EntityRepository;

/**
 * Třída spravující mapové body.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PlacePointRepository extends EntityRepository
{
    public function findById(int $id) : ?PlacePoint
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(PlacePoint $placePoint) : void
    {
        $this->_em->persist($placePoint);
        $this->_em->flush();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(PlacePoint $placePoint) : void
    {
        $this->_em->remove($placePoint);
        $this->_em->flush();
    }
}
