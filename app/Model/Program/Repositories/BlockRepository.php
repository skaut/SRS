<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Application\Application;
use App\Model\Application\SubeventsApplication;
use App\Model\Enums\ApplicationState;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Program\Block;
use App\Model\Program\Category;
use App\Model\Program\Exceptions\BlockCapacityInsufficientException;
use App\Model\Program\Program;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

use function array_map;
use function assert;

/**
 * Třída spravující programové bloky.
 */
class BlockRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Block::class);
    }

    /** @return Collection<int, Block> */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací blok podle id.
     */
    public function findById(int|null $id): Block|null
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
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
     * @return Collection<int, Block>
     */
    public function findBlocksByIds(array $ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids));

        return $this->getRepository()->matching($criteria);
    }

    /** @return Collection<int, Block> */
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
     * @return Collection<int, Block>
     */
    public function findUserAllowed(User $user, bool $paidOnly): Collection
    {
        if (! $user->isApproved()) {
            return new ArrayCollection();
        }

        $notAllowedStates = [ApplicationState::CANCELED, ApplicationState::CANCELED_NOT_PAID, ApplicationState::CANCELED_TRANSFERED];
        if ($paidOnly) {
            $notAllowedStates[] = ApplicationState::WAITING_FOR_PAYMENT;
        }

        $qb = $this->createQueryBuilder('b')
            ->where('b.category IS NULL OR EXISTS (
                SELECT c.id FROM ' . Category::class . ' c JOIN c.registerableRoles r JOIN r.users u
                WHERE c = b.category AND u = :user
            )')
            ->andWhere('EXISTS (
                SELECT sa.id FROM ' . SubeventsApplication::class . ' sa JOIN sa.subevents s
                WHERE s = b.subevent AND sa.user = :user AND sa.validTo IS NULL AND sa.state NOT IN (:notAllowedStates)
            )')
            ->setParameter('user', $user)
            ->setParameter('notAllowedStates', $notAllowedStates);

        if ($paidOnly) {
            $qb->andWhere('EXISTS (
                SELECT ra.id FROM ' . Application::class . ' ra JOIN ra.roles rar
                WHERE ra.user = :user AND ra.validTo IS NULL AND ra.state NOT IN (:notAllowedStates)
            )');
        }

        return new ArrayCollection($qb->getQuery()->getResult());
    }

    public function getMinBlockAllowedCapacity(Block $block): int|null
    {
        $result = $this->createQueryBuilder('b')
            ->select('count(pa) c')
            ->join('b.programs', 'p')
            ->join('p.programApplications', 'pa', 'WITH', 'pa.alternate = false')
            ->where('b = :block')
            ->groupBy('p')
            ->orderBy('c', 'DESC')
            ->setParameter('block', $block)
            ->getQuery()
            ->getResult();

        return empty($result) ? null : $result[0]['c'];
    }

    /**
     * Uloží blok.
     */
    public function save(Block $block): void
    {
        $this->em->wrapInTransaction(static function (EntityManager $em) use ($block): void {
            if ($block->getCapacity() !== null) {
                foreach ($block->getPrograms() as $program) {
                    $program = $em->getRepository(Program::class)->find($program->getId(), LockMode::PESSIMISTIC_WRITE);
                    assert($program instanceof Program);
                    if ($program->getAttendeesCount() > $block->getCapacity()) {
                        throw new BlockCapacityInsufficientException();
                    }
                }
            }

            $em->persist($block);
            $em->flush();
        });
    }

    /**
     * Odstraní blok.
     */
    public function remove(Block $block): void
    {
        $this->em->remove($block);
        $this->em->flush();
    }
}
