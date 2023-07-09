<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Program\Room;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

use function array_map;

/**
 * Třída spravující místnosti.
 */
class RoomRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Room::class);
    }

    /** @return Collection<int, Room> */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací místnost podle id.
     */
    public function findById(int|null $id): Room|null
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací názvy všech místností.
     *
     * @return string[]
     */
    public function findAllNames(): array
    {
        $names = $this->createQueryBuilder('r')
            ->select('r.name')
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $names);
    }

    /**
     * Vrací názvy místností, kromě místnosti s id.
     *
     * @return string[]
     */
    public function findOthersNames(int $id): array
    {
        $names = $this->createQueryBuilder('r')
            ->select('r.name')
            ->where('r.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $names);
    }

    /**
     * Vrací místnosti podle id.
     *
     * @param int[] $ids
     *
     * @return Collection<int, Room>
     */
    public function findRoomsByIds(array $ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids));

        return $this->getRepository()->matching($criteria);
    }

    /**
     * Je v místnosti jiný program ve stejnou dobu?
     */
    public function hasOverlappingProgram(Room $room, int|null $programId, DateTimeImmutable $start, DateTimeImmutable $end): bool
    {
        $result = $this->createQueryBuilder('r')
            ->select('count(r)')
            ->join('r.programs', 'p')
            ->join('p.block', 'b')
            ->where("p.start < :end AND DATE_ADD(p.start, (b.duration * 60), 'second') > :start")
            ->andWhere('r = :room')
            ->andWhere('p.id != :pid')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('room', $room)
            ->setParameter('pid', $programId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== 0;
    }

    /**
     * Uloží místnost.
     */
    public function save(Room $room): void
    {
        $this->em->persist($room);
        $this->em->flush();
    }

    /**
     * Odstraní místnost.
     */
    public function remove(Room $room): void
    {
        $this->em->remove($room);
        $this->em->flush();
    }
}
