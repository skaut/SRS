<?php

declare(strict_types=1);

namespace App\Model\Group\Repositories;

use App\Model\Acl\Role;
use App\Model\Enums\ApplicationState;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Program\Block;
use App\Model\Program\Program;
use App\Model\Group\Group;
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
class GroupRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Group::class);
    }

    /** @return Collection<int, Group> */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací uživatele podle id.
     */
    public function findById(int|null $id): Group|null
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací uživatele podle id.
     *
     * @param int[] $ids
     *
     * @return Collection<int, Group>
     */
    public function findUsersByIds(array $ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids));

        return $this->getRepository()->matching($criteria);
    }

    /**
     * Vrací uživatele přihlášené na podakce.
     *
     * @param int[] $subeventsIds
     *
     * @return Collection<int, Group>
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
     * @return Collection<int, Group>
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
     * Uloží uživatele.
     */
    public function save(Group $group): void
    {
        $this->em->persist($group);
        $this->em->flush();
    }

    /**
     * Odstraní externího uživatele.
     */
    public function remove(Group $group): void
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
            ->join('u.applications', 'sa', 'WITH', 'sa.validTo IS NULL AND sa.state != :stateCanceled AND sa.state != :stateCanceledNotPaid')
            ->join('sa.subevents', 's')
            ->where('u.approved = true')
            ->andWhere('s = :subevent')
            ->setParameter('subevent', $block->getSubevent())
            ->setParameter('stateCanceled', ApplicationState::CANCELED)
            ->setParameter('stateCanceledNotPaid', ApplicationState::CANCELED_NOT_PAID);

        if ($block->getCategory() !== null) {
            $qb = $qb->join('u.roles', 'r')
                ->join('r.registerableCategories', 'c')
                ->andWhere('c = :category')
                ->setParameter('category', $block->getCategory());
        }

        if ($paidOnly) {
            $qb = $qb->join('u.applications', 'ra', 'WITH', 'ra.validTo IS NULL AND ra.state != :stateCanceled AND ra.state != :stateCanceledNotPaid AND ra.state != :stateWaitingForPayment')
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
