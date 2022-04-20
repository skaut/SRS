<?php

declare(strict_types=1);

namespace App\Model\SkautIs\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\SkautIs\SkautIsCourse;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

use function array_map;

/**
 * Třída spravující skautIS kurzy
 */
class SkautIsCourseRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, SkautIsCourse::class);
    }

    /**
     * @return Collection<int, SkautIsCourse>
     */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací skautIS kurz podle id
     */
    public function findById(?int $id): ?SkautIsCourse
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Uloží skautIS kurz
     */
    public function save(SkautIsCourse $skautIsCourse): void
    {
        $this->em->persist($skautIsCourse);
        $this->em->flush();
    }

    /**
     * Odstraní skautIS kurz
     */
    public function remove(SkautIsCourse $skautIsCourse): void
    {
        $this->em->remove($skautIsCourse);
        $this->em->flush();
    }

    /**
     * Odstraní všechny skautIS kurzy
     */
    public function removeAll(): void
    {
        foreach ($this->getRepository()->findAll() as $skautIsCourse) {
            $this->em->remove($skautIsCourse);
        }

        $this->em->flush();
    }

    /**
     * Vrací id skautIS kurzů
     *
     * @param Collection<int, SkautIsCourse> $skautIsCourses
     *
     * @return int[]
     */
    public function findSkautIsCoursesIds(Collection $skautIsCourses): array
    {
        return array_map(static fn (SkautIsCourse $skautIsCourse) => $skautIsCourse->getId(), $skautIsCourses->toArray());
    }

    /**
     * Vrací skautIS kurzy podle id
     *
     * @param int[] $ids
     *
     * @return Collection<int, SkautIsCourse>
     */
    public function findSkautIsCoursesByIds(array $ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);

        return $this->getRepository()->matching($criteria);
    }

    /**
     * Vrací seznam skautIS kurzů jako možnosti pro select
     *
     * @return string[]
     */
    public function getSkautIsCoursesOptions(): array
    {
        $skautIsCourses = $this->createQueryBuilder('c')
            ->select('c.id, c.name')
            ->orderBy('c.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($skautIsCourses as $skautIsCourse) {
            $options[$skautIsCourse['id']] = $skautIsCourse['name'];
        }

        return $options;
    }
}
