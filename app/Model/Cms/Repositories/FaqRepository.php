<?php

declare(strict_types=1);

namespace App\Model\Cms\Repositories;

use App\Model\Cms\Faq;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;

use const PHP_INT_MAX;

/**
 * Třída spravující FAQ.
 */
class FaqRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Faq::class);
    }

    /**
     * Vrací otázku podle id.
     */
    public function findById(?int $id): ?Faq
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací id poslední otázky.
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findLastId(): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('MAX(f.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací poslední pozici.
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findLastPosition(): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('MAX(f.position)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací publikované otázky seřazené podle pozice.
     *
     * @return Faq[]
     */
    public function findPublishedOrderedByPosition(): array
    {
        return $this->getRepository()->findBy(['public' => true], ['position' => 'ASC']);
    }

    /**
     * Uloží otázku.
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    public function save(Faq $faq): void
    {
        if (! $faq->getPosition()) {
            $faq->setPosition($this->findLastPosition() + 1);
        }

        $this->em->persist($faq);
        $this->em->flush();
    }

    /**
     * Odstraní otázku.
     *
     */
    public function remove(Faq $faq): void
    {
        $this->em->remove($faq);
        $this->em->flush();
    }

    /**
     * Přesune otázku mezi otázky s id prevId a nextId.
     *
     * @throws ORMException
     */
    public function sort(int $itemId, int $prevId, int $nextId): void
    {
        $item = $this->getRepository()->find($itemId);
        $prev = $prevId ? $this->getRepository()->find($prevId) : null;
        $next = $nextId ? $this->getRepository()->find($nextId) : null;

        $itemsToMoveUp = $this->createQueryBuilder('i')
            ->where('i.position <= :position')
            ->setParameter('position', $prev ? $prev->getPosition() : 0)
            ->andWhere('i.position > :position2')
            ->setParameter('position2', $item->getPosition())
            ->getQuery()
            ->getResult();

        foreach ($itemsToMoveUp as $t) {
            $t->setPosition($t->getPosition() - 1);
            $this->em->persist($t);
        }

        $itemsToMoveDown = $this->createQueryBuilder('i')
            ->where('i.position >= :position')
            ->setParameter('position', $next ? $next->getPosition() : PHP_INT_MAX)
            ->andWhere('i.position < :position2')
            ->setParameter('position2', $item->getPosition())
            ->getQuery()
            ->getResult();

        foreach ($itemsToMoveDown as $t) {
            $t->setPosition($t->getPosition() + 1);
            $this->em->persist($t);
        }

        if ($prev) {
            $item->setPosition($prev->getPosition() + 1);
        } elseif ($next) {
            $item->setPosition($next->getPosition() - 1);
        } else {
            $item->setPosition(1);
        }

        $this->em->persist($item);
        $this->em->flush();
    }
}
