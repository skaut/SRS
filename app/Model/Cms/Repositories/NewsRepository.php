<?php

declare(strict_types=1);

namespace App\Model\Cms\Repositories;

use App\Model\Cms\News;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * Třída spravující aktuality.
 */
class NewsRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, News::class);
    }

    /**
     * Vrací aktualitu podle id.
     */
    public function findById(int|null $id): News|null
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací id poslední aktuality.
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findLastId(): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('MAX(n.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací posledních $maxCount publikovaných aktualit.
     *
     * @return News[]
     */
    public function findPublishedOrderedByPinnedAndDate(int|null $maxCount): array
    {
        return $this->createQueryBuilder('n')
            ->where($this->createQueryBuilder('n')->expr()->lte('n.published', 'CURRENT_TIMESTAMP()'))
            ->orderBy('n.pinned', 'DESC')
            ->addOrderBy('n.published', 'DESC')
            ->setMaxResults($maxCount)
            ->getQuery()
            ->getResult();
    }

    /**
     * Uloží aktualitu.
     */
    public function save(News $news): void
    {
        $this->em->persist($news);
        $this->em->flush();
    }

    /**
     * Odstraní aktualitu.
     */
    public function remove(News $document): void
    {
        $this->em->remove($document);
        $this->em->flush();
    }
}
