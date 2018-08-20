<?php

declare(strict_types=1);

namespace App\Model\User;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\EntityRepository;

/**
 * Třída spravující variabilní symboly.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class VariableSymbolRepository extends EntityRepository
{
    /**
     * Uloží variabilní symbol.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(VariableSymbol $variableSymbol) : void
    {
        $this->_em->persist($variableSymbol);
        $this->_em->flush();
    }
}
