<?php

namespace App\Model\CMS\Content;


use Kdyby\Doctrine\EntityRepository;

class ContentRepository extends EntityRepository
{
    public function findContentById($id) {
        return $this->find($id);
    }

    public function removeContent($id)
    {
        $content = $this->find($id);

        $itemsToMoveUp = $this->createQueryBuilder('c')
            ->join('c.page', 'p')
            ->where('p.id = :id')->setParameter('id', $content->getPage()->getId())
            ->andWhere('c.area = :area')->setParameter('area', $content->getArea())
            ->andWhere('c.position > :position')->setParameter('position', $content->getPosition())
            ->getQuery()
            ->getResult();

        foreach ($itemsToMoveUp as $t) {
            $t->setPosition($t->getPosition() - 1);
            $this->_em->persist($t);
        }

        $this->_em->remove($content);
        $this->_em->flush();
    }

//    public function changePosition($itemId, $prevId, $nextId) {
//        $item = $this->find($itemId);
//        $prev = $prevId ? $this->find($prevId) : null;
//        $next = $nextId ? $this->find($nextId) : null;
//
//        $itemsToMoveUp = $this->createQueryBuilder('i')
//            ->where('i.position <= :position')
//            ->setParameter('position', $prev ? $prev->getPosition() : 0)
//            ->andWhere('i.position > :position2')
//            ->setParameter('position2', $item->getPosition())
//            ->getQuery()
//            ->getResult();
//
//        foreach ($itemsToMoveUp as $t) {
//            $t->setPosition($t->getPosition() - 1);
//            $this->_em->persist($t);
//        }
//
//        $itemsToMoveDown = $this->createQueryBuilder('i')
//            ->where('i.position >= :position')
//            ->setParameter('position', $next ? $next->getPosition() : PHP_INT_MAX)
//            ->andWhere('i.position < :position2')
//            ->setParameter('position2', $item->getPosition())
//            ->getQuery()
//            ->getResult();
//
//        foreach ($itemsToMoveDown as $t) {
//            $t->setPosition($t->getPosition() + 1);
//            $this->_em->persist($t);
//        }
//
//        if ($prev) {
//            $item->setPosition($prev->getPosition() + 1);
//        } else if ($next) {
//            $item->setPosition($next->getPosition() - 1);
//        } else {
//            $item->setPosition(1);
//        }
//
//        $this->_em->persist($item);
//        $this->_em->flush();
//    }
}