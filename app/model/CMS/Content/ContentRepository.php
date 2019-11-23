<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující obsahy webu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class ContentRepository extends EntityRepository
{
    /**
     * Uloží obsah.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Content $content) : void
    {
        $this->_em->persist($content);
        $this->_em->flush();
    }

    /**
     * Odstraní obsah.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Content $content) : void
    {
        $this->_em->remove($content);
        $this->_em->flush();
    }
}
