<?php

declare(strict_types=1);

namespace App\Model\Application\Repositories;

use App\Model\Application\VariableSymbol;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující variabilní symboly
 */
class VariableSymbolRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, VariableSymbol::class);
    }

    /**
     * Uloží variabilní symbol
     */
    public function save(VariableSymbol $variableSymbol): void
    {
        $this->em->persist($variableSymbol);
        $this->em->flush();
    }
}
