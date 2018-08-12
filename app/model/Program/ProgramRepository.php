<?php
declare(strict_types=1);

namespace App\Model\Program;

use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující programy.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramRepository extends EntityRepository
{
    /**
     * Vrací program podle id.
     * @param $id
     * @return Program|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Uloží program.
     * @param Program $program
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Program $program)
    {
        $this->_em->persist($program);
        $this->_em->flush();
    }

    /**
     * Odstraní program.
     * @param Program $program
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(Program $program)
    {
        $this->_em->remove($program);
        $this->_em->flush();
    }

    /**
     * Vrací id podle programů.
     * @param $programs
     * @return array
     */
    public function findProgramsIds($programs)
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $programs->toArray());
    }

    /**
     * Vrací programy, na které je uživatel zapsaný a jsou v danné kategorii.
     * @param User $user
     * @param Category $category
     * @return array
     */
    public function findUserRegisteredAndInCategory(User $user, Category $category)
    {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->join('p.block', 'b')
            ->join('p.attendees', 'a')
            ->where('b.category = :category')->setParameter('category', $category)
            ->andWhere('a = :user')->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací programy zablokované (programy stejného bloku a překrývající se programy) přihlášením se na program.
     * @param Program $program
     * @return int[]
     * @throws \Exception
     */
    public function findBlockedProgramsIdsByProgram(Program $program)
    {
        return array_merge(
            $this->findOtherProgramsWithSameBlockIds($program),
            $this->findOverlappingProgramsIds($program)
        );
    }

    /**
     * Vrací programy stejného bloku.
     * @param Program $program
     * @return int[]
     */
    public function findOtherProgramsWithSameBlockIds(Program $program)
    {
        $programs = $this->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.block', 'b')
            ->where('b.id = :bid')->setParameter('bid', $program->getBlock()->getId())
            ->andWhere('p.id != :pid')->setParameter('pid', $program->getId())
            ->getQuery()
            ->getScalarResult();
        return array_map('intval', array_map('current', $programs));
    }

    /**
     * Vrací programy s překrývajícím se časem.
     * @param Program $program
     * @return int[]
     * @throws \Exception
     */
    public function findOverlappingProgramsIds(Program $program)
    {
        $start = $program->getStart();
        $end = $program->getEnd();

        $programs = $this->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.block', 'b')
            ->where($this->createQueryBuilder()->expr()->orX(
                "(p.start < :end) AND (DATE_ADD(p.start, (b.duration * 60), 'second') > :start)",
                "(p.start < :end) AND (:start < (DATE_ADD(p.start, (b.duration * 60), 'second')))"
            ))
            ->andWhere('p.id != :pid')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('pid', $program->getId())
            ->getQuery()
            ->getScalarResult();
        return array_map('intval', array_map('current', $programs));
    }

    /**
     * Překrývá se program s jiným programem?
     * @param Program $program
     * @param \DateTime $start
     * @param \DateTime $end
     * @return bool
     */
    public function hasOverlappingProgram(Program $program, \DateTime $start, \DateTime $end)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.block', 'b')
            ->where($this->createQueryBuilder()->expr()->orX(
                "(p.start < :end) AND (DATE_ADD(p.start, (b.duration * 60), 'second') > :start)",
                "(p.start < :end) AND (:start < (DATE_ADD(p.start, (b.duration * 60), 'second')))"
            ))
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($program->getId()) {
            $qb = $qb
                ->andWhere('p.id != :pid')
                ->setParameter('pid', $program->getId());
        }

        return !empty($qb->getQuery()->getResult());
    }

    /**
     * Překrývá se s jiným programem, který je automaticky zapisovaný.
     * @param Program $program
     * @param \DateTime $start
     * @param \DateTime $end
     * @return bool
     */
    public function hasOverlappingAutoRegisterProgram(Program $program, \DateTime $start, \DateTime $end)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.block', 'b')
            ->where($this->createQueryBuilder()->expr()->orX(
                "(p.start < :end) AND (DATE_ADD(p.start, (b.duration * 60), 'second') > :start)",
                "(p.start < :end) AND (:start < (DATE_ADD(p.start, (b.duration * 60), 'second')))"
            ))
            ->andWhere('b.mandatory = 2')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($program->getId()) {
            $qb = $qb
                ->andWhere('p.id != :pid')
                ->setParameter('pid', $program->getId());
        }

        return !empty($qb->getQuery()->getResult());
    }

    /**
     * Vrací programy povolené pro kategorie a podakce.
     * @param Collection $categories
     * @param Collection $subevents
     * @return Collection|Program[]
     */
    public function findAllowedForCategoriesAndSubevents(Collection $categories, Collection $subevents): Collection
    {
        $result = $this->createQueryBuilder('p')
            ->select('p')
            ->join('p.block', 'b')
            ->leftJoin('b.category', 'c')
            ->leftJoin('b.subevent', 's')
            ->where('(b.category IS NULL OR c IN (:categories))')->setParameter('categories', $categories)
            ->andWhere('s IN (:subevents)')->setParameter('subevents', $subevents)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }
}
