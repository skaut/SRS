<?php

declare(strict_types=1);

namespace App\Model\Structure;

use Doctrine\ORM\EntityRepository;

/**
 * Třída spravující slevy.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
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
     */
    public function save(Discount $discount) : void
    {
        $this->_em->persist($discount);
        $this->_em->flush();
    }

    /**
     * Odstraní slevu.
     */
    public function remove(Discount $discount) : void
    {
        $this->_em->remove($discount);
        $this->_em->flush();
    }
}
