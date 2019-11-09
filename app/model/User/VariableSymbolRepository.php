<?php

declare(strict_types=1);

namespace App\Model\User;

use App\Model\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující variabilní symboly.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class VariableSymbolRepository extends EntityRepository
{
    /**
     * Uloží variabilní symbol.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(VariableSymbol $variableSymbol) : void
    {
        $this->_em->persist($variableSymbol);
        $this->_em->flush();
    }
}
