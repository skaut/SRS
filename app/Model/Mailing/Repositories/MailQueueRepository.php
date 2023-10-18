<?php

declare(strict_types=1);

namespace App\Model\Mailing\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Mailing\MailQueue;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující frontu e-mailů.
 */
class MailQueueRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, MailQueue::class);
    }

    /** @return Collection<int, MailQueue> */
    public function findMailsToSend(int $limit): Collection
    {
        $result = $this->createQueryBuilder('m')
            ->where('m.sent = false')
            ->orderBy('m.enqueueDatetime')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Uloží e-mail.
     */
    public function save(MailQueue $mailQueue): void
    {
        $this->em->persist($mailQueue);
        $this->em->flush();
    }
}
