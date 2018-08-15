<?php
declare(strict_types=1);

namespace App\Model\User;

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
     * @param VariableSymbol $variableSymbol
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(VariableSymbol $variableSymbol): void
    {
        $this->_em->persist($variableSymbol);
        $this->_em->flush();
    }
}
