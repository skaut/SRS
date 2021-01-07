<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
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
class BlockRepository extends AbstractRepository
{
    /**
     * @return Collection<Block>
     */
    public function findAll() : Collection
    {
        $result = $this->em->getRepository(Block::class)->findAll();
        return new ArrayCollection($result);
    }

    /**
     * Vrací blok podle id.
     */
    public function findById(?int $id) : ?Block
    {
        return $this->em->getRepository(Block::class)->findOneBy(['id' => $id]);
    }

    /**
     * Vrací poslední id.
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findLastId() : int
    {
        return (int) $this->em->getRepository(Block::class)
            ->createQueryBuilder('b')
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
        $names = $this->em->getRepository(Block::class)
            ->createQueryBuilder('b')
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
        return $this->em->getRepository(Block::class)
            ->createQueryBuilder('b')
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
        return $this->em->getRepository(Block::class)
            ->createQueryBuilder('b')
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
        $names = $this->em->getRepository(Block::class)
            ->createQueryBuilder('b')
            ->select('b.name')
            ->where('b.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $names);
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

        return $this->em->getRepository(Block::class)->matching($criteria);
    }

    /**
     * @return Collection<Block>
     */
    public function findUserAttends(User $user) : Collection
    {
        $result = $this->em->getRepository(Block::class)
            ->createQueryBuilder('b')
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
        $this->em->persist($block);
        $this->em->flush();
    }

    /**
     * Odstraní blok.
     *
     * @throws ORMException
     */
    public function remove(Block $block) : void
    {
        foreach ($block->getPrograms() as $program) {
            $this->em->remove($program);
        }

        $this->em->remove($block);
        $this->em->flush();
    }
}
