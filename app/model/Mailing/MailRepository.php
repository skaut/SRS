<?php

namespace App\Model\Mailing;


use Kdyby\Doctrine\EntityRepository;

class MailRepository extends EntityRepository
{
    public function save(Mail $mail)
    {
        $this->_em->persist($mail);
        $this->_em->flush();
    }
}