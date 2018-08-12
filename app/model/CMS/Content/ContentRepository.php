<?php
declare(strict_types=1);

namespace App\Model\CMS\Content;

use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující obsahy webu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ContentRepository extends EntityRepository
{
    /**
     * Uloží obsah.
     * @param Content $content
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Content $content): void
    {
        $this->_em->persist($content);
        $this->_em->flush();
    }

    /**
     * Odstraní obsah.
     * @param Content $content
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(Content $content): void
    {
        $this->_em->remove($content);
        $this->_em->flush();
    }
}
