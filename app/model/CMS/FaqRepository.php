<?php
declare(strict_types=1);

namespace App\Model\CMS;

use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující FAQ.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class FaqRepository extends EntityRepository
{
    /**
     * Vrací otázku podle id.
     * @param $id
     * @return Faq|null
     */
    public function findById(int $id): ?Faq
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací id poslední otázky.
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
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
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
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
     * @return array
     */
    public function findPublishedOrderedByPosition(): array
    {
        return $this->findBy(['public' => TRUE], ['position' => 'ASC']);
    }

    /**
     * Uloží otázku.
     * @param Faq $faq
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Faq $faq): void
    {
        if (!$faq->getPosition())
            $faq->setPosition($this->findLastPosition() + 1);

        $this->_em->persist($faq);
        $this->_em->flush();
    }

    /**
     * Odstraní otázku.
     * @param Faq $faq
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(Faq $faq): void
    {
        $this->_em->remove($faq);
        $this->_em->flush();
    }

    /**
     * Přesune otázku mezi otázky s id prevId a nextId.
     * @param $itemId
     * @param $prevId
     * @param $nextId
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function sort(int $itemId, int $prevId, int $nextId): void
    {
        $item = $this->find($itemId);
        $prev = $prevId ? $this->find($prevId) : NULL;
        $next = $nextId ? $this->find($nextId) : NULL;

        $itemsToMoveUp = $this->createQueryBuilder('i')
            ->where('i.position <= :position')
            ->setParameter('position', $prev ? $prev->getPosition() : 0)
            ->andWhere('i.position > :position2')
            ->setParameter('position2', $item->getPosition())
            ->getQuery()
            ->getResult();

        foreach ($itemsToMoveUp as $t) {
            $t->setPosition($t->getPosition() - 1);
            $this->_em->persist($t);
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
            $this->_em->persist($t);
        }

        if ($prev) {
            $item->setPosition($prev->getPosition() + 1);
        } else if ($next) {
            $item->setPosition($next->getPosition() - 1);
        } else {
            $item->setPosition(1);
        }

        $this->_em->persist($item);
        $this->_em->flush();
    }
}
