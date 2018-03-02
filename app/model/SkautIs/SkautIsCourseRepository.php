<?php
declare(strict_types=1);

namespace App\Model\SkautIs;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující skautIS kurzy.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SkautIsCourseRepository extends EntityRepository
{
    /**
     * Vrací skautIS kurz podle id.
     * @param int $id
     * @return SkautIsCourse|null
     */
    public function findById(int $id): ?SkautIsCourse
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Uloží skautIS kurz.
     * @param SkautIsCourse $skautIsCourse
     */
    public function save(SkautIsCourse $skautIsCourse): void
    {
        $this->_em->persist($skautIsCourse);
        $this->_em->flush();
    }

    /**
     * Odstraní skautIS kurz.
     * @param SkautIsCourse $skautIsCourse
     */
    public function remove(SkautIsCourse $skautIsCourse): void
    {
        $this->_em->remove($skautIsCourse);
        $this->_em->flush();
    }

    public function removeAll(): void
    {
        foreach ($this->findAll() as $skautIsCourse)
            $this->_em->remove($skautIsCourse);
        $this->_em->flush();
    }

    /**
     * Vrací id skautIS kurzů.
     * @param Collection|SkautIsCourse[] $skautIsCourses
     * @return array
     */
    public function findSkautIsCoursesIds(Collection $skautIsCourses): array
    {
        return array_map(function (SkautIsCourse $skautIsCourse) {
            return $skautIsCourse->getId();
        }, $skautIsCourses->toArray());
    }

    /**
     * Vrací skautIS kurzy podle id.
     * @param array $ids
     * @return Collection|SkautIsCourse[]
     */
    public function findSkautIsCoursesByIds(array $ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    /**
     * Vrací seznam skautIS kurzů jako možnosti pro select.
     * @return array
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
