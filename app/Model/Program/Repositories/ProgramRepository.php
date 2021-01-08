<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Enums\ApplicationState;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Program\Block;
use App\Model\Program\Category;
use App\Model\Program\Program;
use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující programy.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class ProgramRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Program::class);
    }

    /**
     * @return Collection<Program>
     */
    public function findAll() : Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací program podle id.
     */
    public function findById(?int $id) : ?Program
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * @return Collection<Program>
     */
    public function findUserAttends(User $user) : Collection
    {
        $result = $this->createQueryBuilder('p')
            ->join('p.programApplications', 'a', 'WITH', 'a.user = :user AND a.alternate = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Vrací programy, na které je uživatel zapsaný a jsou v danné kategorii.
     *
     * @return Collection<Program>
     */
    public function findUserAttendsAndCategory(User $user, Category $category) : Collection
    {
        $result = $this->createQueryBuilder('p')
            ->join('p.programApplications', 'a', 'WITH', 'a.user = :user AND a.alternate = false')
            ->join('p.block', 'b', 'WITH', 'b.category = :category')
            ->setParameter('user', $user)
            ->setParameter('category', $category)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * @return Collection<Program>
     */
    public function findUserAlternatesAndBlock(User $user, Block $block) : Collection
    {
        $result = $this->createQueryBuilder('p')
            ->join('p.programApplications', 'a', 'WITH', 'a.user = :user AND a.alternate = true')
            ->where('p.block = :block')
            ->setParameter('user', $user)
            ->setParameter('block', $block)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Vrací programy povolené pro kategorie a podakce.
     *
     * @return Collection<Program>
     */
    public function findUserAllowed(User $user) : Collection
    {
        $result = $this->createQueryBuilder('p')
            ->join('p.block', 'b')
            ->leftJoin('b.category', 'c')
            ->join('c.registerableRoles', 'r')
            ->join('r.users', 'u')
            ->join('b.subevent', 's')
            ->join('s.applications', 'a', 'WITH', 'a.validTo IS NULL AND a.state != :stateCanceled AND a.state != :stateCanceledNotPaid')
            ->where('u IS NULL OR u = :user')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->setParameter('stateCanceled', ApplicationState::CANCELED)
            ->setParameter('stateCanceledNotPaid', ApplicationState::CANCELED_NOT_PAID)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Vrací programy zablokované (programy stejného bloku a překrývající se programy) přihlášením se na program.
     *
     * @return Collection<Program>
     */
    public function findBlockedByProgram(Program $program) : Collection
    {
        $start = $program->getStart();
        $end   = $program->getEnd();

        $result = $this->createQueryBuilder('p')
            ->join('p.block', 'b')
            ->where('p != :program')
            ->andWhere("b = :block OR (p.start < :end AND DATE_ADD(p.start, (b.duration * 60), 'second') > :start)")
            ->setParameter('program', $program)
            ->setParameter('block', $program->getBlock())
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Překrývá se program s jiným programem?
     */
    public function hasOverlappingProgram(?int $programId, DateTimeImmutable $start, DateTimeImmutable $end) : bool
    {
        $result = $this->createQueryBuilder('p')
            ->select('count(p)')
            ->join('p.block', 'b')
            ->where('p.id != :pid OR :pid IS NULL')
            ->andWhere("p.start < :end AND DATE_ADD(p.start, (b.duration * 60), 'second') > :start")
            ->setParameter('pid', $programId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== 0;
    }

    /**
     * Překrývá se s jiným programem, který je automaticky zapisovaný?
     */
    public function hasOverlappingAutoRegisteredProgram(?int $programId, DateTimeImmutable $start, DateTimeImmutable $end) : bool
    {
        $result = $this->createQueryBuilder('p')
            ->select('count(p)')
            ->join('p.block', 'b', 'WITH', 'b.mandatory = :autoRegistered')
            ->where('p.id != :pid OR :pid IS NULL')
            ->andWhere("p.start < :end AND DATE_ADD(p.start, (b.duration * 60), 'second') > :start")
            ->setParameter('pid', $programId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('autoRegistered', ProgramMandatoryType::AUTO_REGISTERED)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== 0;
    }

    /**
     * Uloží program.
     */
    public function save(Program $program) : void
    {
        $this->em->persist($program);
        $this->em->flush();
    }

    /**
     * Odstraní program.
     */
    public function remove(Program $program) : void
    {
        $this->em->remove($program);
        $this->em->flush();
    }
}
