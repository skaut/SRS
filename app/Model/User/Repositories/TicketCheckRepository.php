<?php

declare(strict_types=1);

namespace App\Model\User\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\User\TicketCheck;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;

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
     * Uloží kontrolu vstupenky.
     *
     * @throws ORMException
     */
    public function save(TicketCheck $ticketCheck): void
    {
        $this->em->persist($ticketCheck);
        $this->em->flush();
    }

    /**
     * Odstraní kontrolu vstupenky.
     *
     * @throws ORMException
     */
    public function remove(TicketCheck $ticketCheck): void
    {
        $this->em->remove($ticketCheck);
        $this->em->flush();
    }
}
