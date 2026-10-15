<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Enums\ProgramMandatoryType;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Infrastructure\Repositories\RestrictsToUserAllowedBlocks;
use App\Model\Program\Category;
use App\Model\Program\Program;
use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující programy.
 */
class ProgramRepository extends AbstractRepository
{
    use RestrictsToUserAllowedBlocks;

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
        $qb = $this->createQueryBuilder('p')
            ->join('p.block', 'b');

        $this->restrictToUserAllowedBlocks($qb, 'b', $user, $paidOnly);

        return new ArrayCollection($qb->getQuery()->getResult());
    }

    /**
     * Vrací id, blok, začátek a délku všech programů.
     *
     * Slouží k výpočtu překryvů a programů stejného bloku v paměti, aby se nemusel pro
     * každý program zvlášť pouštět dotaz s DATE_ADD, který nedokáže využít index.
     *
     * @return array<int, array{id: int, blockId: int, start: DateTimeImmutable, duration: int}>
     */
    public function findAllTimings(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id AS id, IDENTITY(p.block) AS blockId, p.start AS start, b.duration AS duration')
            ->join('p.block', 'b')
            ->getQuery()
            ->getResult();
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
