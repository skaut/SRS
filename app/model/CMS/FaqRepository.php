<?php

namespace App\Model\CMS;


use Kdyby\Doctrine\EntityRepository;

class FaqRepository extends EntityRepository
{
    /**
     * @param $id
     * @return Faq|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @return int
     */
    public function findLastId()
    {
        return $this->createQueryBuilder('f')
            ->select('MAX(f.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return int
     */
    public function findLastPosition()
    {
        return $this->createQueryBuilder('f')
            ->select('MAX(f.position)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param Faq $faq
     */
    public function save(Faq $faq)
    {
        if (!$faq->getPosition())
            $faq->setPosition($this->findLastPosition() + 1);

        $this->_em->persist($faq);
        $this->_em->flush();
    }

    /**
     * @param Faq $faq
     */
    public function remove(Faq $faq)
    {
        $this->_em->remove($faq);
        $this->_em->flush();
    }

    /**
     * @param $itemId
     * @param $prevId
     * @param $nextId
     */
    public function sort($itemId, $prevId, $nextId)
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
        } else if ($next) {
            $item->setPosition($next->getPosition() - 1);
        } else {
            $item->setPosition(1);
        }

        $this->_em->persist($item);
        $this->_em->flush();
    }
}