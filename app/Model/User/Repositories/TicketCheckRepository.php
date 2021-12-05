<?php

declare(strict_types=1);

namespace App\Model\User\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Structure\Subevent;
use App\Model\User\TicketCheck;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující kontroly vstupenek.
 */
class TicketCheckRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, TicketCheck::class);
    }

    /**
     * @return Collection<int, TicketCheck>
     */
    public function findByUserAndSubevent(User $user, Subevent $subevent): Collection
    {
        return $this->getRepository()
            ->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.subevent = :subevent')
            ->orderBy('t.dateTime')
            ->setParameter('user', $user)
            ->setParameter('subevent', $subevent)
            ->getQuery()
            ->getResult();
    }

    public function save(TicketCheck $ticketCheck): void
    {
        $this->em->persist($ticketCheck);
        $this->em->flush();
    }

    public function remove(TicketCheck $ticketCheck): void
    {
        $this->em->remove($ticketCheck);
        $this->em->flush();
    }
}
