<?php

declare(strict_types=1);

namespace App\Model\User\Repositories;

use App\Model\Acl\Role;
use App\Model\Enums\ApplicationState;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Program\Block;
use App\Model\Program\Program;
use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;

use function array_map;
use function count;

/**
 * Třída spravující uživatele.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class UserRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, User::class);
    }

    /**
     * @return Collection<User>
     */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací uživatele podle id.
     */
    public function findById(?int $id): ?User
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací uživatele podle skautISUserId.
     */
    public function findBySkautISUserId(int $skautISUserId): ?User
    {
        return $this->getRepository()->findOneBy(['skautISUserId' => $skautISUserId]);
    }

    /**
     * Vrací uživatele podle id.
     *
     * @param int[] $ids
     *
     * @return Collection|User[]
     */
    public function findUsersByIds(array $ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids));

        return $this->getRepository()->matching($criteria);
    }

    /**
     * Vrací id uživatelů.
     *
     * @param Collection|User[] $users
     *
     * @return int[]
     */
    public function findUsersIds(Collection $users): array
    {
        return array_map(static function (User $user) {
            return $user->getId();
        }, $users->toArray());
    }

    /**
     * Vrací jména uživatelů obsahující zadaný text, seřazená podle zobrazovaného jména.
     *
     * @return string[]
     */
    public function findNamesByLikeDisplayNameOrderedByDisplayName(string $text): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.id, u.displayName')
            ->where('u.displayName LIKE :text')->setParameter('text', '%' . $text . '%')
            ->addOrderBy('u.displayName')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací uživatele, kteří se synchronizují s účastníky skautIS akce.
     *
     * @return User[]
     */
    public function findAllSyncedWithSkautIS(): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.syncedWithSkautIS = true')
            ->andWhere('u.external = false')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací uživatele v roli.
     *
     * @return User[]
     */
    public function findAllInRole(Role $role): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.id = :id')->setParameter('id', $role->getId())
            ->orderBy('u.displayName')
            ->getQuery()->execute();
    }

    /**
     * Vrací schválené uživatele v roli.
     *
     * @return User[]
     */
    public function findAllApprovedInRole(Role $role): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.id = :id')->setParameter('id', $role->getId())
            ->andWhere('u.approved = true')
            ->orderBy('u.displayName')
            ->getQuery()->execute();
    }

    /**
     * Vrací uživatele v rolích.
     *
     * @param int[] $rolesIds
     *
     * @return User[]
     */
    public function findAllInRoles(array $rolesIds): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.id IN (:ids)')->setParameter('ids', $rolesIds)
            ->orderBy('u.displayName')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací schválené uživatele v rolích.
     *
     * @param int[] $rolesIds
     *
     * @return User[]
     */
    public function findAllApprovedInRoles(array $rolesIds): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.id IN (:ids)')->setParameter('ids', $rolesIds)
            ->andWhere('u.approved = true')
            ->orderBy('u.displayName')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací uživatele přihlášené na podakce.
     *
     * @param int[] $subeventsIds
     *
     * @return Collection|User[]
     */
    public function findAllWithSubevents(array $subeventsIds): Collection
    {
        $result = $this->createQueryBuilder('u')
            ->join('u.applications', 'a')
            ->join('a.subevents', 's')
            ->where('a.validTo IS NULL')
            ->andWhere('a.state IN (:states)')
            ->andWhere('s.id IN (:ids)')
            ->setParameter('states', [ApplicationState::PAID, ApplicationState::PAID_FREE, ApplicationState::WAITING_FOR_PAYMENT])
            ->setParameter('ids', $subeventsIds)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Vrací uživatele s přihláškou čekající na zaplacení.
     *
     * @return Collection|User[]
     */
    public function findAllWithWaitingForPaymentApplication(): Collection
    {
        $result = $this->createQueryBuilder('u')
            ->join('u.applications', 'a')
            ->where('a.validTo IS NULL')
            ->andWhere('a.state = :state')
            ->setParameter('state', ApplicationState::WAITING_FOR_PAYMENT)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Vrací uživatele, kteří se mohou na program přihlásit.
     *
     * @return Collection|User[]
     */
    public function findBlockAllowed(Block $block): Collection
    {
        $qb = $this->createQueryBuilder('u')
//            ->join('r.permissions', 'p')
            ->join('u.applications', 'a', 'WITH', 'a.validTo IS NULL AND a.state != :stateCanceled AND a.state != :stateCanceledNotPaid')
            ->join('a.subevents', 's')
//            ->where('p.name = :permission')
            ->where('s = :subevent')
//            ->setParameter('permission', Permission::CHOOSE_PROGRAMS) //todo: odstranit?
            ->setParameter('subevent', $block->getSubevent())
            ->setParameter('stateCanceled', ApplicationState::CANCELED)
            ->setParameter('stateCanceledNotPaid', ApplicationState::CANCELED_NOT_PAID);

        if ($block->getCategory() !== null) {
            $qb = $qb->join('u.roles', 'r')
                ->join('r.registerableCategories', 'c')
                ->andWhere('c = :category')
                ->setParameter('category', $block->getCategory());
        }

        return new ArrayCollection($qb->getQuery()->getResult());
    }

    /**
     * @return Collection<User>
     */
    public function findBlockAttendees(Block $block): Collection
    {
        $result = $this->createQueryBuilder('u')
            ->leftJoin('u.programApplications', 'a')
            ->leftJoin('a.program', 'p')
            ->where('p.block = :block')->setParameter('block', $block)
            ->andWhere('a.alternate = false')
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * @return Collection<User>
     */
    public function findProgramAttendees(Program $program): Collection
    {
        $result = $this->createQueryBuilder('u')
            ->leftJoin('u.programApplications', 'a')
            ->where('a.program = :program')->setParameter('program', $program)
            ->andWhere('a.alternate = false')
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    public function countProgramAttendees(Program $program): int
    {
        return $this->createQueryBuilder('u')
            ->select('count(u)')
            ->leftJoin('u.programApplications', 'a')
            ->where('a.program = :program')->setParameter('program', $program)
            ->andWhere('a.alternate = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Collection<User>
     */
    public function findProgramAlternates(Program $program): Collection
    {
        $result = $this->createQueryBuilder('u')
            ->leftJoin('u.programApplications', 'a')
            ->where('a.program = :program')->setParameter('program', $program)
            ->andWhere('a.alternate = true')
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    public function findProgramFirstAlternate(Program $program): ?User
    {
        $result = $this->createQueryBuilder('u')
            ->leftJoin('u.programApplications', 'a')
            ->where('a.program = :program')->setParameter('program', $program)
            ->andWhere('a.alternate = true')
            ->orderBy('a.createdAt')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return count($result) === 1 ? $result[0] : null;
    }

    public function countProgramAlternates(Program $program): int
    {
        return $this->createQueryBuilder('u')
            ->select('count(u)')
            ->leftJoin('u.programApplications', 'a')
            ->where('a.program = :program')->setParameter('program', $program)
            ->andWhere('a.alternate = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací uživatele jako možnosti pro select.
     *
     * @return string[]
     */
    public function getUsersOptions(): array
    {
        $users = $this->createQueryBuilder('u')
            ->select('u.id, u.displayName')
            ->orderBy('u.displayName')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($users as $user) {
            $options[$user['id']] = $user['displayName'];
        }

        return $options;
    }

    /**
     * Vrací lektory jako možnosti pro select.
     *
     * @return string[]
     */
    public function getLectorsOptions(): array
    {
        $lectors = $this->createQueryBuilder('u')
            ->select('u.id, u.displayName')
            ->join('u.roles', 'r')
            ->where('r.systemName = :name')->setParameter('name', Role::LECTOR)
            ->andWhere('u.approved = true')
            ->orderBy('u.displayName')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($lectors as $lector) {
            $options[$lector['id']] = $lector['displayName'];
        }

        return $options;
    }

    /**
     * Má lektor jiný program ve stejný čas?
     */
    public function hasOverlappingLecturersProgram(User $lector, ?int $programId, DateTimeImmutable $start, DateTimeImmutable $end): bool
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u.id')
            ->join('u.lecturersBlocks', 'b')
            ->join('b.programs', 'p')
            ->where("p.start < :end AND DATE_ADD(p.start, (b.duration * 60), 'second') > :start")
            ->andWhere('u.id = :uid')
            ->andWhere('(p.id != :pid) or (:pid IS NULL)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('uid', $lector->getId())
            ->setParameter('pid', $programId);

        return ! empty($qb->getQuery()->getResult());
    }

    /**
     * Uloží uživatele.
     *
     * @throws ORMException
     */
    public function save(User $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * Odstraní externího uživatele.
     *
     * @throws ORMException
     */
    public function remove(User $user): void
    {
        foreach ($user->getCustomInputValues() as $customInputValue) {
            $this->em->remove($customInputValue);
        }

        foreach ($user->getApplications() as $application) {
            $this->em->remove($application);
        }

        $this->em->remove($user);
        $this->em->flush();
    }
}
