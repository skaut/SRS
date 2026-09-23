<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Application\Application;
use App\Model\Application\SubeventsApplication;
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
use Exception;

/**
 * Třída spravující programy.
 */
class ProgramRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Program::class);
    }

    /** @return Collection<int, Program> */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací program podle id.
     */
    public function findById(int|null $id): Program|null
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /** @return Collection<int, Program> */
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
     * @return Collection<int, Program>
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
     * @return Collection<int, Program>
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

        $qb = $this->createQueryBuilder('p')
            ->join('p.block', 'b')
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

    /**
     * Vrací programy se stejným blokem.
     *
     * @return Collection<int, Program>
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
     * @return Collection<int, Program>
     *
     * @throws Exception
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
    public function hasOverlappingProgram(int|null $programId, DateTimeImmutable $start, DateTimeImmutable $end): bool
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
    public function hasOverlappingAutoRegisteredProgram(int|null $programId, DateTimeImmutable $start, DateTimeImmutable $end): bool
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
