<?php

declare(strict_types=1);

namespace App\Model\User;

use Doctrine\ORM\EntityRepository;

/**
 * Třída spravující variabilní symboly.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class VariableSymbolRepository extends EntityRepository
{
    /**
     * Uloží variabilní symbol.
     */
    public function save(VariableSymbol $variableSymbol) : void
    {
        $this->_em->persist($variableSymbol);
        $this->_em->flush();
    }
}
