<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Enums\ApplicationState;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Infrastructure\Repositories\AbstractRepository;
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
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací program podle id.
     */
    public function findById(?int $id): ?Program
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * @return Collection<Program>
     */
    public function findUserAttends(User $user): Collection
    {
        $result = $this->createQueryBuilder('p')
            ->join('p.programApplications', 'a', 'WITH', 'a.user = :user AND a.alternate = false')
            ->orderBy('p.start')
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
    public function findUserAttendsAndCategory(User $user, Category $category): Collection
    {
        $result = $this->createQueryBuilder('p')
            ->join('p.programApplications', 'a', 'WITH', 'a.user = :user AND a.alternate = false')
            ->join('p.block', 'b', 'WITH', 'b.category = :category')
            ->orderBy('p.start')
            ->setParameter('user', $user)
            ->setParameter('category', $category)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Vrací programy povolené pro uživatele.
     *
     * @return Collection<Program>
     */
    public function findUserAllowed(User $user, bool $paidOnly): Collection
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.block', 'b')
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
     * Vrací programy se stejným blokem.
     *
     * @return Collection<Program>
     */
    public function findSameBlockPrograms(Program $program): Collection
    {
        $result = $this->createQueryBuilder('p')
            ->join('p.block', 'b')
            ->where('p != :program')
            ->andWhere('p.block = :block')
            ->setParameter('program', $program)
            ->setParameter('block', $program->getBlock())
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Vrací programy překrývající se s programem.
     *
     * @return Collection<Program>
     */
    public function findOverlappingPrograms(Program $program): Collection
    {
        $result = $this->createQueryBuilder('p')
            ->join('p.block', 'b')
            ->where('p != :program')
            ->andWhere("p.start < :end AND DATE_ADD(p.start, (b.duration * 60), 'second') > :start")
            ->setParameter('program', $program)
            ->setParameter('start', $program->getStart())
            ->setParameter('end', $program->getEnd())
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Překrývá se program s jiným programem?
     */
    public function hasOverlappingProgram(?int $programId, DateTimeImmutable $start, DateTimeImmutable $end): bool
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
    public function hasOverlappingAutoRegisteredProgram(?int $programId, DateTimeImmutable $start, DateTimeImmutable $end): bool
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
    public function save(Program $program): void
    {
        $this->em->persist($program);
        $this->em->flush();
    }

    /**
     * Odstraní program.
     */
    public function remove(Program $program): void
    {
        $this->em->remove($program);
        $this->em->flush();
    }
}
