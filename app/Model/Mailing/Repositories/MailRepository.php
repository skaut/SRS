<?php

declare(strict_types=1);

namespace App\Model\Mailing\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Mailing\Mail;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující historii e-mailů.
 */
class MailRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Mail::class);
    }

    /**
     * Uloží e-mail.
     */
    public function save(Mail $mail): void
    {
        $this->em->persist($mail);
        $this->em->flush();
    }
}
