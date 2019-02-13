<?php

declare(strict_types=1);

namespace App\Model\User;

use App\Model\ACL\Permission;
use App\Model\ACL\Role;
use App\Model\Enums\ApplicationState;
use App\Model\Program\Block;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\EntityRepository;
use function array_map;

/**
 * Třída spravující uživatele.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class UserRepository extends EntityRepository
{
    /**
     * Vrací uživatele podle id.
     */
    public function findById(?int $id) : ?User
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací uživatele podle skautISUserId.
     */
    public function findBySkautISUserId(int $skautISUserId) : ?User
    {
        return $this->findOneBy(['skautISUserId' => $skautISUserId]);
    }

    /**
     * Vrací uživatele podle id.
     * @param int[] $ids
     * @return Collection|User[]
     */
    public function findUsersByIds(array $ids) : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids));
        return $this->matching($criteria);
    }

    /**
     * Vrací id uživatelů.
     * @param Collection|User[] $users
     * @return int[]
     */
    public function findUsersIds(Collection $users) : array
    {
        return array_map(function (User $user) {
            return $user->getId();
        }, $users->toArray());
    }

    /**
     * Vrací jména uživatelů obsahující zadaný text, seřazená podle zobrazovaného jména.
     * @return string[]
     */
    public function findNamesByLikeDisplayNameOrderedByDisplayName(string $text) : array
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
     * @return User[]
     */
    public function findAllSyncedWithSkautIS() : array
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
     * @return User[]
     */
    public function findAllInRole(Role $role) : array
    {
        return $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.id = :id')->setParameter('id', $role->getId())
            ->orderBy('u.displayName')
            ->getQuery()->execute();
    }

    /**
     * Vrací uživatele v rolích.
     * @param int[] $rolesIds
     * @return User[]
     */
    public function findAllInRoles(array $rolesIds) : array
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
     * @param int[] $rolesIds
     * @return User[]
     */
    public function findAllApprovedInRoles(array $rolesIds) : array
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
     * @param int[] $subeventsIds
     * @return Collection|User[]
     */
    public function findAllWithSubevents(array $subeventsIds) : Collection
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
     * @return Collection|User[]
     */
    public function findAllWithWaitingForPaymentApplication() : Collection
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
     * @return Collection|User[]
     */
    public function findBlockAllowed(Block $block) : Collection
    {
        $qb = $this->createQueryBuilder('u')
            ->innerJoin('u.roles', 'r')
            ->innerJoin('r.permissions', 'p')
            ->innerJoin('u.applications', 'a')
            ->innerJoin('a.subevents', 's')
            ->where('p.name = :permission')
            ->andWhere('s.id = :sid')
            ->andWhere('a.validTo IS NULL')
            ->andWhere('(a.state = \'' . ApplicationState::PAID . '\' OR a.state = \'' . ApplicationState::PAID_FREE
                . '\' OR a.state = \'' . ApplicationState::WAITING_FOR_PAYMENT . '\')')
            ->setParameter('permission', Permission::CHOOSE_PROGRAMS)
            ->setParameter('sid', $block->getSubevent()->getId());

        if ($block->getCategory()) {
            $qb = $qb->innerJoin('r.registerableCategories', 'c')
                ->andWhere('c.id = :cid')
                ->setParameter('cid', $block->getCategory()->getId());
        }

        return new ArrayCollection($qb->getQuery()->getResult());
    }

    /**
     * Vrací uživatele jako možnosti pro select.
     * @return string[]
     */
    public function getUsersOptions() : array
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
     * @return string[]
     */
    public function getLectorsOptions() : array
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
     * Uloží uživatele.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(User $user) : void
    {
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Odstraní externího uživatele.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(User $user) : void
    {
        foreach ($user->getCustomInputValues() as $customInputValue) {
            $this->_em->remove($customInputValue);
        }

        foreach ($user->getApplications() as $application) {
            $this->_em->remove($application);
        }

        $this->_em->remove($user);
        $this->_em->flush();
    }
}
