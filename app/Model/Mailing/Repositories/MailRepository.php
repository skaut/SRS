<?php

declare(strict_types=1);

namespace App\Model\Mailing\Repositories;

use App\Model\Mailing\Mail;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující historii e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class MailRepository extends EntityRepository
{
    /**
     * Uloží e-mail.
     *
     * @throws ORMException
     */
    public function save(Mail $mail) : void
    {
        $this->_em->persist($mail);
        $this->_em->flush();
    }
}
