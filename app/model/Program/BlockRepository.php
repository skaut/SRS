<?php
declare(strict_types=1);

namespace App\Model\Program;


use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující programové bloky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BlockRepository extends EntityRepository
{
    /**
     * Vrací blok podle id.
     * @param $id
     * @return Block|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací poslední id.
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastId()
    {
        return $this->createQueryBuilder('b')
            ->select('MAX(b.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací názvy všech bloků.
     * @return array
     */
    public function findAllNames()
    {
        $names = $this->createQueryBuilder('b')
            ->select('b.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * Vrací všechny bloky seřazené podle názvu.
     * @return array
     */
    public function findAllOrderedByName()
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací všechny bloky nezařezené v kategorii, seřazené podle názvu.
     * @return array
     */
    public function findAllUncategorizedOrderedByName()
    {
        return $this->createQueryBuilder('b')
            ->where('b.category IS NULL')
            ->orderBy('b.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací názvy ostatních bloků, kromě bloku se zadaným id.
     * @param $id
     * @return array
     */
    public function findOthersNames($id)
    {
        $names = $this->createQueryBuilder('b')
            ->select('b.name')
            ->where('b.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * Vrací bloky podle textu obsaženého v názvu, seřazené podle názvu.
     * @param $text
     * @param bool $unassignedOnly
     * @return array
     */
    public function findByLikeNameOrderedByName($text, $unassignedOnly = FALSE)
    {
        $qb = $this->createQueryBuilder('b')
            ->select('b')
            ->where('b.name LIKE :text')->setParameter('text', '%' . $text . '%');

        if ($unassignedOnly) {
            $qb = $qb->leftJoin('b.programs', 'p')
                ->andWhere('SIZE(b.programs) = 0');
        }

        return $qb->orderBy('b.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací bloky, které jsou pro uživatele povinné a není na ně přihlášený.
     * @param User $user
     * @param Collection $categories
     * @param Collection $subevents
     * @return Collection|Block[]
     */
    public function findMandatoryForCategoriesAndSubevents(User $user, Collection $categories, Collection $subevents): Collection
    {
        $usersBlocks = $this->createQueryBuilder('b')
            ->select('b')
            ->leftJoin('b.programs', 'p')
            ->leftJoin('p.attendees', 'u')
            ->where('u = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $qb = $this->createQueryBuilder('b')
            ->select('b')
            ->leftJoin('b.category', 'c')
            ->where($this->createQueryBuilder()->expr()->orX(
                'c IN (:categories)',
                'b.category IS NULL'
            ))
            ->andWhere('b.subevent IN (:usersSubevents)')
            ->andWhere('b.mandatory > 0')
            ->setParameter('categories', $categories)
            ->setParameter('usersSubevents', $subevents);

        if (!empty($usersBlocks)) {
            $qb = $qb
                ->andWhere('b NOT IN (:usersBlocks)')
                ->setParameter('usersBlocks', $usersBlocks);
        }

        return new ArrayCollection($qb->getQuery()->getResult());
    }

    /**
     * Vrací id bloků.
     * @param $blocks
     * @return array
     */
    public function findBlocksIds($blocks)
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $blocks->toArray());
    }

    /**
     * Vrací bloky podle id.
     * @param $ids
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findBlocksByIds($ids)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids));
        return $this->matching($criteria);
    }

    /**
     * Uloží blok.
     * @param Block $block
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Block $block)
    {
        $this->_em->persist($block);
        $this->_em->flush();
    }

    /**
     * Odstraní blok.
     * @param Block $block
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(Block $block)
    {
        foreach ($block->getPrograms() as $program)
            $this->_em->remove($program);

        $this->_em->remove($block);
        $this->_em->flush();
    }
}
