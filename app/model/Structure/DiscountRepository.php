<?php
declare(strict_types=1);

namespace App\Model\Structure;

use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující slevy.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DiscountRepository extends EntityRepository
{
    /**
     * Vrací slevu podle id.
     * @param $id
     * @return Discount|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Uloží slevu.
     * @param Discount $discount
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Discount $discount)
    {
        $this->_em->persist($discount);
        $this->_em->flush();
    }

    /**
     * Odstraní slevu.
     * @param Discount $discount
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(Discount $discount)
    {
        $this->_em->remove($discount);
        $this->_em->flush();
    }
}
