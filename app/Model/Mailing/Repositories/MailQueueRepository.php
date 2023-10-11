<?php

declare(strict_types=1);

namespace App\Model\Mailing\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Mailing\MailQueue;
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

    /**
     * Uloží e-mail.
     */
    public function save(MailQueue $mailQueue): void
    {
        $this->em->persist($mailQueue);
        $this->em->flush();
    }
}
