<?php

declare(strict_types=1);

namespace App\Model\Settings\CustomInput;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\EntityRepository;
use const PHP_INT_MAX;

/**
 * Třída spravující vlastní pole přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomInputRepository extends EntityRepository
{
    /**
     * Vrací pole podle id.
     * @param $id
     */
    public function findById(int $id) : ?CustomInput
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací všechna pole seřazená podle pozice.
     * @return CustomInput[]
     */
    public function findAllOrderedByPosition() : array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.position')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrátí pozici posledního pole.
     * @throws NonUniqueResultException
     */
    public function findLastPosition() : int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('MAX(i.position)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Uloží pole.
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(CustomInput $input) : void
    {
        if (! $input->getPosition()) {
            $input->setPosition($this->findLastPosition() + 1);
        }

        $this->_em->persist($input);
        $this->_em->flush();
    }

    /**
     * Odstraní pole.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(CustomInput $input) : void
    {
        foreach ($input->getCustomInputValues() as $customInputValue) {
            $this->_em->remove($customInputValue);
        }

        $this->_em->remove($input);
        $this->_em->flush();
    }

    /**
     * Přesune pole mezi pole s id prevId a nextId.
     * @param $itemId
     * @param $prevId
     * @param $nextId
     * @throws ORMException
     * @throws OptimisticLockException
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
