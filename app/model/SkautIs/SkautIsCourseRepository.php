<?php

declare(strict_types=1);

namespace App\Model\SkautIs;

use App\Model\EntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use function array_map;

/**
 * Třída spravující skautIS kurzy.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class SkautIsCourseRepository extends EntityRepository
{
    /**
     * Vrací skautIS kurz podle id.
     */
    public function findById(?int $id) : ?SkautIsCourse
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Uloží skautIS kurz.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(SkautIsCourse $skautIsCourse) : void
    {
        $this->_em->persist($skautIsCourse);
        $this->_em->flush();
    }

    /**
     * Odstraní skautIS kurz.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(SkautIsCourse $skautIsCourse) : void
    {
        $this->_em->remove($skautIsCourse);
        $this->_em->flush();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeAll() : void
    {
        foreach ($this->findAll() as $skautIsCourse) {
            $this->_em->remove($skautIsCourse);
        }
        $this->_em->flush();
    }

    /**
     * Vrací id skautIS kurzů.
     * @param Collection|SkautIsCourse[] $skautIsCourses
     * @return int[]
     */
    public function findSkautIsCoursesIds(Collection $skautIsCourses) : array
    {
        return array_map(function (SkautIsCourse $skautIsCourse) {
            return $skautIsCourse->getId();
        }, $skautIsCourses->toArray());
    }

    /**
     * Vrací skautIS kurzy podle id.
     * @param int[] $ids
     * @return Collection|SkautIsCourse[]
     */
    public function findSkautIsCoursesByIds(array $ids) : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    /**
     * Vrací seznam skautIS kurzů jako možnosti pro select.
     * @return string[]
     */
    public function getSkautIsCoursesOptions() : array
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
