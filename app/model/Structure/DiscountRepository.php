<?php

declare(strict_types=1);

namespace App\Model\Structure;

use App\Model\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující slevy.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DiscountRepository extends EntityRepository
{
    /**
     * Vrací slevu podle id.
     */
    public function findById(?int $id) : ?Discount
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Uloží slevu.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Discount $discount) : void
    {
        $this->_em->persist($discount);
        $this->_em->flush();
    }

    /**
     * Odstraní slevu.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Discount $discount) : void
    {
        $this->_em->remove($discount);
        $this->_em->flush();
    }
}
