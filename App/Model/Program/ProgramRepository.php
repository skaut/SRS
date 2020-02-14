<?php

declare(strict_types=1);

namespace App\Model\Program;

use App\Model\Enums\ProgramMandatoryType;
use App\Model\Structure\Subevent;
use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Exception;
use function array_map;
use function array_merge;

/**
 * Třída spravující programy.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class ProgramRepository extends EntityRepository
{
    /**
     * Vrací program podle id.
     */
    public function findById(?int $id) : ?Program
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Uloží program.
     *
     * @throws ORMException
     */
    public function save(Program $program) : void
    {
        $this->_em->persist($program);
        $this->_em->flush();
    }

    /**
     * Odstraní program.
     *
     * @throws ORMException
     */
    public function remove(Program $program) : void
    {
        $this->_em->remove($program);
        $this->_em->flush();
    }

    /**
     * Vrací id podle programů.
     *
     * @param Collection|Program[] $programs
     *
     * @return int[]
     */
    public function findProgramsIds(Collection $programs) : array
    {
        return array_map(static function (Program $o) {
            return $o->getId();
        }, $programs->toArray());
    }

    /**
     * Vrací programy, na které je uživatel zapsaný a jsou v danné kategorii.
     *
     * @return Program[]
     */
    public function findUserRegisteredAndInCategory(User $user, Category $category) : array
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
     *
     * @return int[]
     *
     * @throws Exception
     */
    public function findBlockedProgramsIdsByProgram(Program $program) : array
    {
        return array_merge(
            $this->findOtherProgramsWithSameBlockIds($program),
            $this->findOverlappingProgramsIds($program)
        );
    }

    /**
     * Vrací programy stejného bloku.
     *
     * @return int[]
     */
    public function findOtherProgramsWithSameBlockIds(Program $program) : array
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
     *
     * @return int[]
     *
     * @throws Exception
     */
    public function findOverlappingProgramsIds(Program $program) : array
    {
        $start = $program->getStart();
        $end   = $program->getEnd();

        $programs = $this->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.block', 'b')
            ->where($this->createQueryBuilder('p')->expr()->orX(
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
     */
    public function hasOverlappingProgram(?int $programId, DateTimeImmutable $start, DateTimeImmutable $end) : bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.block', 'b')
            ->where($this->createQueryBuilder('p')->expr()->orX(
                "(p.start < :end) AND (DATE_ADD(p.start, (b.duration * 60), 'second') > :start)",
                "(p.start < :end) AND (:start < (DATE_ADD(p.start, (b.duration * 60), 'second')))"
            ))
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($programId) {
            $qb = $qb
                ->andWhere('p.id != :pid')
                ->setParameter('pid', $programId);
        }

        return ! empty($qb->getQuery()->getResult());
    }

    /**
     * Překrývá se s jiným programem, který je automaticky zapisovaný.
     */
    public function hasOverlappingAutoRegisteredProgram(?int $programId, DateTimeImmutable $start, DateTimeImmutable $end) : bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.block', 'b')
            ->where($this->createQueryBuilder('p')->expr()->orX(
                "(p.start < :end) AND (DATE_ADD(p.start, (b.duration * 60), 'second') > :start)",
                "(p.start < :end) AND (:start < (DATE_ADD(p.start, (b.duration * 60), 'second')))"
            ))
            ->andWhere('b.mandatory = :auto_registered')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('auto_registered', ProgramMandatoryType::AUTO_REGISTERED);

        if ($programId) {
            $qb = $qb
                ->andWhere('p.id != :pid')
                ->setParameter('pid', $programId);
        }

        return ! empty($qb->getQuery()->getResult());
    }

    /**
     * Vrací programy povolené pro kategorie a podakce.
     *
     * @param Collection|Category[] $categories
     * @param Collection|Subevent[] $subevents
     *
     * @return Collection|Program[]
     */
    public function findAllowedForCategoriesAndSubevents(Collection $categories, Collection $subevents) : Collection
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

    public function incrementOccupancy(Program $program) : void
    {
        $this->_em->createQuery('UPDATE App\Model\Program\Program p SET p.occupancy = p.occupancy + 1 WHERE p.id = :pid')
            ->setParameter('pid', $program->getId())
            ->getResult();
    }

    public function decrementOccupancy(Program $program) : void
    {
        $this->_em->createQuery('UPDATE App\Model\Program\Program p SET p.occupancy = p.occupancy - 1 WHERE p.id = :pid')
            ->setParameter('pid', $program->getId())
            ->getResult();
    }
}
