<?php

namespace App\Model\Settings\CustomInput;

use Kdyby\Doctrine\EntityRepository;

class CustomInputRepository extends EntityRepository
{
    public function addCheckBox($name) {
        $checkbox = new CustomCheckbox();
        $checkbox->setName($name);
        $checkbox->setPosition($this->countBy() + 1);
        $this->_em->persist($checkbox);
        $this->_em->flush();
    }

    public function addText($name) {
        $text = new CustomText();
        $text->setName($name);
        $text->setPosition($this->countBy() + 1);
        $this->_em->persist($text);
        $this->_em->flush();
    }

    public function removeInput($id)
    {
        $input = $this->find($id);

        $itemsToMoveUp = $this->createQueryBuilder('i')
            ->andWhere('i.position > :position')
            ->setParameter('position', $input->getPosition())
            ->getQuery()
            ->getResult();

        foreach ($itemsToMoveUp as $t) {
            $t->setPosition($t->getPosition() - 1);
            $this->_em->persist($t);
        }

        $this->_em->remove($input);
        $this->_em->flush();
    }

    public function renameInput($id, $name) {
        $input = $this->find($id);
        $input->setName($name);
        $this->_em->flush();
    }

    public function changePosition($itemId, $prevId, $nextId) {
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

        $this->_em->persist($item)->flush();
    }
}