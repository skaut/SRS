<?php

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
}
