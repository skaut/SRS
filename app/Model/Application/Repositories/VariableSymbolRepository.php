<?php

declare(strict_types=1);

namespace App\Model\Application\Repositories;

use App\Model\Application\VariableSymbol;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující variabilní symboly.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class VariableSymbolRepository extends AbstractRepository
{
    /**
     * Uloží variabilní symbol.
     *
     * @throws ORMException
     */
    public function save(VariableSymbol $variableSymbol): void
    {
        $this->_em->persist($variableSymbol);
        $this->_em->flush();
    }
}
