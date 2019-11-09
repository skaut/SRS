<?php

declare(strict_types=1);

namespace App\Model\Mailing;

use App\Model\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující historii e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailRepository extends EntityRepository
{
    /**
     * Uloží e-mail.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Mail $mail) : void
    {
        $this->_em->persist($mail);
        $this->_em->flush();
    }
}
