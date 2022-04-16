<?php

declare(strict_types=1);

namespace App\Model\Mailing\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Mailing\Mail;
use App\Model\Mailing\MailBatch;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující dávky e-mailů.
 */
class MailBatchRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Mail::class);
    }

    /**
     * Uloží dávku e-mailů.
     */
    public function save(MailBatch $mail): void
    {
        $this->em->persist($mail);
        $this->em->flush();
    }
}
