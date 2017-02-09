<?php

namespace App\Model\CMS;


use Kdyby\Doctrine\EntityRepository;
use Symfony\Component\Console\Question\Question;

class FaqRepository extends EntityRepository
{
    public function findById($id) {
        return $this->findOneBy(['id' => $id]);
    }

    public function findLastId() {
        return $this->createQueryBuilder('f')
            ->select('MAX(f.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Faq $question) {
        if (!$question->getPosition())
            $question->setPosition($this->countBy() + 1);

        $this->_em->persist($question);
        $this->_em->flush();
    }

    public function remove(Faq $question)
    {
        $this->_em->remove($question);
        $this->_em->flush();
    }

    public function sort($itemId, $prevId, $nextId) {
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