<?php

declare(strict_types=1);

namespace App\Model\Mailing\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Mailing\Mail;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující historii e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class MailRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Mail::class);
    }

    /**
     * Uloží e-mail.
     *
     * @throws ORMException
     */
    public function save(Mail $mail): void
    {
        $this->em->persist($mail);
        $this->em->flush();
    }
}
