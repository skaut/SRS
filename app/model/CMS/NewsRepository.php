<?php

namespace App\Model\CMS;

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
     * @param $id
     * @return News|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací id poslední aktuality.
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastId()
    {
        return $this->createQueryBuilder('n')
            ->select('MAX(n.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací posledních $maxCount publikovaných aktualit.
     * @param $maxCount
     * @return News[]
     */
    public function findPublishedOrderedByPinnedAndDate($maxCount)
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
     * @param News $news
     */
    public function save(News $news)
    {
        $this->_em->persist($news);
        $this->_em->flush();
    }

    /**
     * Odstraní aktualitu.
     * @param News $document
     */
    public function remove(News $document)
    {
        $this->_em->remove($document);
        $this->_em->flush();
    }
}
