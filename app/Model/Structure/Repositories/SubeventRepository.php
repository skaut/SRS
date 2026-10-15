<?php

declare(strict_types=1);

namespace App\Model\Structure\Repositories;

use App\Model\Enums\ApplicationState;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Structure\Subevent;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

use function array_map;

/**
 * Třída spravující podakce.
 */
class SubeventRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Subevent::class);
    }

    /** @return Collection<int, Subevent> */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací počty účastníků podakcí, tedy platných nezrušených přihlášek.
     *
     * @param int[] $subeventsIds
     *
     * @return array<int, int> počet účastníků podle id podakce
     */
    public function countUsersInSubevents(array $subeventsIds): array
    {
        if ($subeventsIds === []) {
            return [];
        }

        $result = $this->createQueryBuilder('s')
            ->select('s.id AS id, COUNT(a.id) AS usersCount')
            ->leftJoin('s.applications', 'a', 'WITH', 'a.validTo IS NULL AND a.state IN (:states)')
            ->where('s.id IN (:ids)')
            ->groupBy('s.id')
            ->setParameter('ids', $subeventsIds)
            ->setParameter('states', [
                ApplicationState::WAITING_FOR_PAYMENT,
                ApplicationState::PAID,
                ApplicationState::PAID_FREE,
                ApplicationState::PAID_TRANSFERED,
            ])
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[(int) $row['id']] = (int) $row['usersCount'];
        }

        return $counts;
    }

    /**
     * Vrací počet účastníků podakce.
     */
    public function countUsersInSubevent(Subevent $subevent): int
    {
        return $this->countUsersInSubevents([$subevent->getId()])[$subevent->getId()] ?? 0;
    }

    /**
     * Vrací podakci podle id.
     */
    public function findById(int|null $id): Subevent|null
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací implicitní podakci.
     */
    public function findImplicit(): Subevent
    {
        return $this->getRepository()->findOneBy(['implicit' => true]);
    }

    /**
     * Vrací názvy všech podakcí.
     *
     * @return string[]
     */
    public function findAllNames(): array
    {
        $names = $this->createQueryBuilder('s')
            ->select('s.name')
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $names);
    }

    /**
     * Vrací podakce splňující podmínku seřazené podle názvu.
     *
     * @return Collection<int, Subevent>
     */
    public function findFilteredSubevents(bool $explicitOnly, bool $registerableNowOnly, bool $notRegisteredOnly, bool $includeUsers, User|null $user = null): Collection
    {
        $qb = $this->createQueryBuilder('s');

        $query = $qb
            ->select('s')
            ->where('1 = 1');

        if ($explicitOnly) {
            $query = $query->andWhere($qb->expr()->eq('s.implicit', 'false'));
        }

        if ($registerableNowOnly) {
            $query = $query
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->lte('s.registerableFrom', 'CURRENT_TIMESTAMP()'),
                    $qb->expr()->isNull('s.registerableFrom'),
                ))
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->gte('s.registerableTo', 'CURRENT_TIMESTAMP()'),
                    $qb->expr()->isNull('s.registerableTo'),
                ));
        }

        if ($notRegisteredOnly) {
            $query = $query->andWhere('s not in (:users_subevents)')->setParameter('users_subevents', $user->getSubevents());
        }

        if ($includeUsers) {
            $query = $query->orWhere('s in (:users_subevents)')->setParameter('users_subevents', $user->getSubevents());
        }

        $result = $query
            ->orderBy('s.name')
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Vrací názvy podakcí, kromě podakce se zadaným id.
     *
     * @return string[]
     */
    public function findOthersNames(int $id): array
    {
        $names = $this->createQueryBuilder('s')
            ->select('s.name')
            ->where('s.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $names);
    }

    /**
     * Vrací podakce podle id.
     *
     * @param int[] $ids
     *
     * @return Collection<int, Subevent>
     */
    public function findSubeventsByIds(array $ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);

        return $this->getRepository()->matching($criteria);
    }

    /**
     * Vrací id podakcí.
     *
     * @param Collection<int, Subevent> $subevents
     *
     * @return int[]
     */
    public function findSubeventsIds(Collection $subevents): array
    {
        return array_map(static fn (Subevent $o) => $o->getId(), $subevents->toArray());
    }

    /**
     * Vrací, zda jsou vytvořeny podakce.
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function explicitSubeventsExists(): bool
    {
        $explicitSubeventsCount = (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.implicit = FALSE')
            ->getQuery()
            ->getSingleScalarResult();

        return $explicitSubeventsCount > 0;
    }

    /**
     * Uloží podakci.
     */
    public function save(Subevent $subevent): void
    {
        $this->em->persist($subevent);
        $this->em->flush();
    }

    /**
     * Odstraní podakci.
     */
    public function remove(Subevent $subevent): void
    {
        $this->em->remove($subevent);
        $this->em->flush();
    }

    /**
     * Atomicky zvýší obsazenost podakce.
     *
     * Jde o UPDATE mimo EntityManager, předaná entita proto zůstává s původní hodnotou
     * occupancy až do konce requestu.
     */
    public function incrementOccupancy(Subevent $subevent): void
    {
        $this->em->createQuery('UPDATE App\Model\Structure\Subevent s SET s.occupancy = s.occupancy + 1 WHERE s.id = :sid')
            ->setParameter('sid', $subevent->getId())
            ->getResult();
    }

    public function decrementOccupancy(Subevent $subevent): void
    {
        $this->em->createQuery('UPDATE App\Model\Structure\Subevent s SET s.occupancy = s.occupancy - 1 WHERE s.id = :sid')
            ->setParameter('sid', $subevent->getId())
            ->getResult();
    }
}
