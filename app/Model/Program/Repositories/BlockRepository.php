<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Enums\ApplicationState;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Program\Block;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
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
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Block::class);
    }

    /**
     * @return Collection<Block>
     */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací blok podle id.
     */
    public function findById(?int $id): ?Block
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací poslední id.
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findLastId(): int
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
    public function findAllNames(): array
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
    public function findAllOrderedByName(): array
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
    public function findAllUncategorizedOrderedByName(): array
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
    public function findOthersNames(int $id): array
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
     * Vrací bloky podle id.
     *
     * @param int[] $ids
     *
     * @return Collection<Block>
     */
    public function findBlocksByIds(array $ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids));

        return $this->getRepository()->matching($criteria);
    }

    /**
     * @return Collection<Block>
     */
    public function findUserAttends(User $user): Collection
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
     * Vrací bloky povolené pro uživatele.
     *
     * @return Collection<Block>
     */
    public function findUserAllowed(User $user, bool $paidOnly): Collection
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.category', 'c')
            ->leftJoin('c.registerableRoles', 'r')
            ->leftJoin('r.users', 'u1')
            ->join('b.subevent', 's')
            ->join('s.applications', 'sa', 'WITH', 'sa.validTo IS NULL AND sa.state != :stateCanceled AND sa.state != :stateCanceledNotPaid')
            ->join('sa.user', 'u2', 'WITH', 'u2.approved = TRUE')
            ->where('c IS NULL OR u1 = :user')
            ->andWhere('u2 = :user')
            ->setParameter('user', $user)
            ->setParameter('stateCanceled', ApplicationState::CANCELED)
            ->setParameter('stateCanceledNotPaid', ApplicationState::CANCELED_NOT_PAID);

        if ($paidOnly) {
            $qb = $qb->join('u2.applications', 'ra', 'WITH', 'ra.validTo IS NULL AND ra.state != :stateCanceled AND ra.state != :stateCanceledNotPaid AND ra.state != :stateWaitingForPayment')
                ->join('ra.roles', 'rar')
                ->andWhere('sa.state != :stateWaitingForPayment')
                ->setParameter('stateWaitingForPayment', ApplicationState::WAITING_FOR_PAYMENT);
        }

        return new ArrayCollection($qb->getQuery()->getResult());
    }

    /**
     * Uloží blok.
     *
     * @throws ORMException
     */
    public function save(Block $block): void
    {
        $this->em->persist($block);
        $this->em->flush();
    }

    /**
     * Odstraní blok.
     *
     * @throws ORMException
     */
    public function remove(Block $block): void
    {
        foreach ($block->getPrograms() as $program) {
            $this->em->remove($program);
        }

        $this->em->remove($block);
        $this->em->flush();
    }
}
