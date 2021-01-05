<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Program\Block;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use function array_map;

/**
 * Třída spravující programové bloky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class BlockRepository extends EntityRepository
{
    /**
     * Vrací blok podle id.
     */
    public function findById(?int $id) : ?Block
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací poslední id.
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findLastId() : int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('MAX(b.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací názvy všech bloků.
     *
     * @return string[]
     */
    public function findAllNames() : array
    {
        $names = $this->createQueryBuilder('b')
            ->select('b.name')
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $names);
    }

    /**
     * Vrací všechny bloky seřazené podle názvu.
     *
     * @return Block[]
     */
    public function findAllOrderedByName() : array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací všechny bloky nezařazené v kategorii, seřazené podle názvu.
     *
     * @return Block[]
     */
    public function findAllUncategorizedOrderedByName() : array
    {
        return $this->createQueryBuilder('b')
            ->where('b.category IS NULL')
            ->orderBy('b.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací názvy ostatních bloků, kromě bloku se zadaným id.
     *
     * @return string[]
     */
    public function findOthersNames(int $id) : array
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
     * Vrací id bloků.
     *
     * @param Collection|Block[] $blocks
     *
     * @return int[]
     */
    public function findBlocksIds(Collection $blocks) : array
    {
        return array_map(static function (Block $o) {
            return $o->getId();
        }, $blocks->toArray());
    }

    /**
     * Vrací bloky podle id.
     *
     * @param int[] $ids
     *
     * @return Collection|Block[]
     */
    public function findBlocksByIds(array $ids) : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids));

        return $this->matching($criteria);
    }

    /**
     * @return Collection<Block>
     */
    public function findUserAttends(User $user) : Collection
    {
        $result = $this->createQueryBuilder('b')
            ->leftJoin('b.programs', 'p')
            ->leftJoin('p.programApplications', 'a')
            ->where('a.user = :user')->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Uloží blok.
     *
     * @throws ORMException
     */
    public function save(Block $block) : void
    {
        $this->_em->persist($block);
        $this->_em->flush();
    }

    /**
     * Odstraní blok.
     *
     * @throws ORMException
     */
    public function remove(Block $block) : void
    {
        foreach ($block->getPrograms() as $program) {
            $this->_em->remove($program);
        }

        $this->_em->remove($block);
        $this->_em->flush();
    }
}
