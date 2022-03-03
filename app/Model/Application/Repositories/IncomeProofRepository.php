<?php

declare(strict_types=1);

namespace App\Model\Application\Repositories;

use App\Model\Application\IncomeProof;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující příjmové doklady.
 */
class IncomeProofRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, IncomeProof::class);
    }

    /**
     * Uloží příjmový doklad.
     */
    public function save(IncomeProof $incomeProof): void
    {
        $this->em->persist($incomeProof);
        $this->em->flush();
    }
}
