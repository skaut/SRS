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
use Doctrine\ORM\QueryBuilder;

use function count;

/**
 * Třída spravující uživatele.
 */
class UserRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, User::class);
    }

    /** @return Collection<int, User> */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací uživatele podle id.
     */
    public function findById(int|null $id): User|null
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací uživatele podle skautISUserId.
     */
    public function findBySkautISUserId(int $skautISUserId): User|null
    {
        return $this->getRepository()->findOneBy(['skautISUserId' => $skautISUserId]);
    }

    /**
     * Vrací uživatele podle id.
     *
     * @param int[] $ids
     *
     * @return Collection<int, User>
     */
    public function findUsersByIds(array $ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids));

        return $this->getRepository()->matching($criteria);
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
     * @return Collection<int, User>
     */
    public function findAllWithSubevents(array $subeventsIds): Collection
    {
        $result = $this->createQueryBuilder('u')
            ->join('u.applications', 'a')
            ->join('a.subevents', 's')
            ->where('a.validTo IS NULL')
            ->andWhere('a.state IN (:states)')
            ->andWhere('s.id IN (:ids)')
            ->setParameter('states', [ApplicationState::PAID, ApplicationState::PAID_FREE, ApplicationState::PAID_TRANSFERED, ApplicationState::WAITING_FOR_PAYMENT])
            ->setParameter('ids', $subeventsIds)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Vrací uživatele s přihláškou čekající na zaplacení.
     *
     * @return Collection<int, User>
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
     * @return Collection<int, User>
     */
    public function findBlockAllowed(Block $block, bool $paidOnly): Collection
    {
        return new ArrayCollection($this->blockAllowedQuery($block, $paidOnly)->getQuery()->getResult());
    }

    /** @return Collection<int, User> */
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

    /** @return Collection<int, User> */
    public function findProgramAttendees(Program $program): Collection
    {
        $result = $this->programAttendeesQuery($program, false)->getQuery()->getResult();

        return new ArrayCollection($result);
    }

    /** @return Collection<int, User> */
    public function findProgramAlternates(Program $program): Collection
    {
        $result = $this->programAttendeesQuery($program, true)->getQuery()->getResult();

        return new ArrayCollection($result);
    }

    public function findProgramFirstAlternate(Program $program): User|null
    {
        $result = $this->programAttendeesQuery($program, true)
            ->orderBy('a.createdAt')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return count($result) === 1 ? $result[0] : null;
    }

    /**
     * Vrací uživatele jako možnosti pro select.
     *
     * @return string[]
     */
    public function getUsersOptions(bool $empty = false): array
    {
        $users = $this->createQueryBuilder('u')
            ->select('u.id, u.displayName')
            ->orderBy('u.displayName')
            ->getQuery()
            ->getResult();

        $options = [];

        if ($empty) {
            $options[0] = '';
        }

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
    public function hasOverlappingLecturersProgram(User $lector, int|null $programId, DateTimeImmutable $start, DateTimeImmutable $end): bool
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
     */
    public function save(User $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * Odstraní externího uživatele.
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

    public function blockAllowedQuery(Block $block, bool $paidOnly): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u')
            ->join('u.applications', 'sa', 'WITH', 'sa.validTo IS NULL AND sa.state != :stateCanceled AND sa.state != :stateCanceledNotPaid AND sa.state != :stateCanceledTransfered')
            ->join('sa.subevents', 's')
            ->where('u.approved = true')
            ->andWhere('s = :subevent')
            ->setParameter('subevent', $block->getSubevent())
            ->setParameter('stateCanceled', ApplicationState::CANCELED)
            ->setParameter('stateCanceledNotPaid', ApplicationState::CANCELED_NOT_PAID)
            ->setParameter('stateCanceledTransfered', ApplicationState::CANCELED_TRANSFERED);

        if ($block->getCategory() !== null) {
            $qb = $qb->join('u.roles', 'r')
                ->join('r.registerableCategories', 'c')
                ->andWhere('c = :category')
                ->setParameter('category', $block->getCategory());
        }

        if ($paidOnly) {
            $qb = $qb->join('u.applications', 'ra', 'WITH', 'ra.validTo IS NULL AND ra.state != :stateCanceled AND ra.state != :stateCanceledNotPaid AND sa.state != :stateCanceledTransfered AND ra.state != :stateWaitingForPayment')
                ->join('ra.roles', 'rar')
                ->andWhere('sa.state != :stateWaitingForPayment')
                ->setParameter('stateWaitingForPayment', ApplicationState::WAITING_FOR_PAYMENT);
        }

        return $qb;
    }

    private function programAttendeesQuery(Program $program, bool|null $alternate): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.programApplications', 'a')
            ->where('a.program = :program')->setParameter('program', $program);

        if ($alternate !== null) {
            $qb = $qb->andWhere('a.alternate = :alternate')
                ->setParameter('alternate', $alternate);
        }

        return $qb;
    }
}
