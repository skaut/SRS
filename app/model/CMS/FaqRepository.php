<?php

declare(strict_types=1);

namespace App\Model\CMS;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use const PHP_INT_MAX;

/**
 * Třída spravující FAQ.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class FaqRepository extends EntityRepository
{
    /**
     * Vrací otázku podle id.
     */
    public function findById(?int $id) : ?Faq
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací id poslední otázky.
     */
    public function findLastId() : int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('MAX(f.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací poslední pozici.
     */
    public function findLastPosition() : int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('MAX(f.position)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací publikované otázky seřazené podle pozice.
     * @return Faq[]
     */
    public function findPublishedOrderedByPosition() : array
    {
        return $this->findBy(['public' => true], ['position' => 'ASC']);
    }

    /**
     * Uloží otázku.
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    public function save(Faq $faq) : void
    {
        if (! $faq->getPosition()) {
            $faq->setPosition($this->findLastPosition() + 1);
        }

        $this->_em->persist($faq);
        $this->_em->flush();
    }

    /**
     * Odstraní otázku.
     */
    public function remove(Faq $faq) : void
    {
        $this->_em->remove($faq);
        $this->_em->flush();
    }

    /**
     * Přesune otázku mezi otázky s id prevId a nextId.
     */
    public function sort(int $itemId, int $prevId, int $nextId) : void
    {
        $item = $this->find($itemId);
        $prev = $prevId ? $this->find($prevId) : null;
        $next = $nextId ? $this->find($nextId) : null;

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
        } elseif ($next) {
            $item->setPosition($next->getPosition() - 1);
        } else {
            $item->setPosition(1);
        }

        $this->_em->persist($item);
        $this->_em->flush();
    }
}
