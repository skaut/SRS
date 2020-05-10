<?php

declare(strict_types=1);

namespace App\Model\User\Application;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;

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
     *
     * @throws ORMException
     */
    public function save(VariableSymbol $variableSymbol) : void
    {
        $this->_em->persist($variableSymbol);
        $this->_em->flush();
    }
}
