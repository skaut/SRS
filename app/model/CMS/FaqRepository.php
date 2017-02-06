<?php

namespace App\Model\CMS;


use Kdyby\Doctrine\EntityRepository;

class FaqRepository extends EntityRepository
{
    public function findQuestionById($id) {
        return $this->find($id);
    }

    public function addQuestion($author, $questionText, $answer = null, $public = false) {
        $question = new Faq();

        $question->setAuthor($author);
        $question->setQuestion($questionText);
        $question->setAnswer($answer);
        $question->setPublic($public);
        $question->setPosition($this->countBy() + 1);

        $this->_em->persist($question);
        $this->_em->flush();

        return $question;
    }

    public function editQuestion($id, $questionText, $answer, $public) {
        $question = $this->find($id);

        $question->setQuestion($questionText);
        $question->setAnswer($answer);
        $question->setPublic($public);

        $this->_em->flush();

        return $question;
    }

    public function setQuestionPublic($id, $public) {
        $question = $this->find($id);
        $question->setPublic($public);
        $this->_em->flush();
        return $question;
    }

    public function removeQuestion($id)
    {
        $question = $this->find($id);

        $itemsToMoveUp = $this->createQueryBuilder('i')
            ->andWhere('i.position > :position')
            ->setParameter('position', $question->getPosition())
            ->getQuery()
            ->getResult();

        foreach ($itemsToMoveUp as $t) {
            $t->setPosition($t->getPosition() - 1);
            $this->_em->persist($t);
        }

        $this->_em->remove($question);
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