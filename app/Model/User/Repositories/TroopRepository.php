<?php

declare(strict_types=1);

namespace App\Model\User\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\User\Troop;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující oddíly.
 */
class TroopRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Troop::class);
    }

    public function findById(int $id): Troop
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    public function findByLeaderId(int $leaderId): ?Troop
    {
        return $this->getRepository()
            ->createQueryBuilder('t')
            ->where('t.leader = :leader_id')
            ->setParameter('leader_id', $leaderId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Troop $troop): void
    {
        $this->em->persist($troop);
        $this->em->flush();
    }

    public function remove(Troop $troop): void
    {
        $this->em->remove($troop);
        $this->em->flush();
    }
}
