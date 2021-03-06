<?php

declare(strict_types=1);

namespace App\Model\Application\Repositories;

use App\Model\Application\IncomeProof;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující příjmové doklady.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class IncomeProofRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, IncomeProof::class);
    }

    /**
     * Uloží příjmový doklad.
     *
     * @throws ORMException
     */
    public function save(IncomeProof $incomeProof): void
    {
        $this->em->persist($incomeProof);
        $this->em->flush();
    }
}
