<?php

declare(strict_types=1);

namespace App\Model\Application\Repositories;

use App\Model\Application\IncomeProof;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující příjmové doklady.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class IncomeProofRepository extends EntityRepository
{
    /**
     * Uloží příjmový doklad.
     *
     * @throws ORMException
     */
    public function save(IncomeProof $incomeProof) : void
    {
        $this->_em->persist($incomeProof);
        $this->_em->flush();
    }
}
