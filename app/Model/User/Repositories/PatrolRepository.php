<?php

declare(strict_types=1);

namespace App\Model\User\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\User\Patrol;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující družiny.
 */
class PatrolRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Patrol::class);
    }

    public function save(Patrol $patrol): void
    {
        $this->em->persist($patrol);
        $this->em->flush();
    }
}
