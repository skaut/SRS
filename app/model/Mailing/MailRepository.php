<?php

namespace App\Model\Mailing;

use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující historii e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailRepository extends EntityRepository
{
    /**
     * Uloží e-mail.
     * @param Mail $mail
     */
    public function save(Mail $mail)
    {
        $this->_em->persist($mail);
        $this->_em->flush();
    }
}