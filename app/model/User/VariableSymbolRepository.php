<?php

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
     */
    public function save(VariableSymbol $variableSymbol)
    {
        $this->_em->persist($variableSymbol);
        $this->_em->flush();
    }
}
