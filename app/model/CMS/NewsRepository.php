<?php

declare(strict_types=1);

namespace App\Model\CMS;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\EntityRepository;

/**
 * Třída spravující aktuality.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class NewsRepository extends EntityRepository
{
    /**
     * Vrací aktualitu podle id.
     */
    public function findById(?int $id) : ?News
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací id poslední aktuality.
     * @throws NonUniqueResultException
     */
    public function findLastId() : int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('MAX(n.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací posledních $maxCount publikovaných aktualit.
     * @return News[]
     */
    public function findPublishedOrderedByPinnedAndDate(?int $maxCount) : array
    {
        return $this->createQueryBuilder('n')
            ->where($this->createQueryBuilder()->expr()->lte('n.published', 'CURRENT_TIMESTAMP()'))
            ->orderBy('n.pinned', 'DESC')
            ->addOrderBy('n.published', 'DESC')
            ->setMaxResults($maxCount)
            ->getQuery()
            ->getResult();
    }

    /**
     * Uloží aktualitu.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(News $news) : void
    {
        $this->_em->persist($news);
        $this->_em->flush();
    }

    /**
     * Odstraní aktualitu.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(News $document) : void
    {
        $this->_em->remove($document);
        $this->_em->flush();
    }
}
