<?php

namespace App\Model\User;

use App\Model\ACL\Permission;
use App\Model\ACL\Role;
use App\Model\Enums\ApplicationState;
use App\Model\Program\Category;
use App\Model\Program\Program;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;


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
     * @param $id
     * @return User|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací uživatele podle skautISUserId.
     * @param $skautISUserId
     * @return User|null
     */
    public function findBySkautISUserId($skautISUserId)
    {
        return $this->findOneBy(['skautISUserId' => $skautISUserId]);
    }

    /**
     * Vrací uživatele podle id.
     * @param $ids
     * @return Collection|User[]
     */
    public function findUsersByIds($ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids));
        return $this->matching($criteria);
    }

    /**
     * Vrací jména uživatelů obsahující zadaný text, seřazená podle zobrazovaného jména.
     * @param $text
     * @return array
     */
    public function findNamesByLikeDisplayNameOrderedByDisplayName($text)
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
     * @return mixed
     */
    public function findAllSyncedWithSkautIS()
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
     * @param Role $role
     * @return mixed
     */
    public function findAllInRole(Role $role)
    {
        return $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.id = :id')->setParameter('id', $role->getId())
            ->orderBy('u.displayName')
            ->getQuery()->execute();
    }

    /**
     * Vrací uživatele v rolích.
     * @param $rolesIds
     * @return mixed
     */
    public function findAllInRoles($rolesIds)
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
     * @param $rolesIds
     * @return mixed
     */
    public function findAllApprovedInRoles($rolesIds)
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
     * Vrací uživatele s přihláškou čekající na zaplacení.
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
     * @param $program
     * @return Collection|User[]
     */
    public function findProgramAllowed(Program $program): Collection
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.programs', 'p', 'WITH', 'p.id = :pid')
            ->innerJoin('u.roles', 'r')
            ->innerJoin('r.permissions', 'per')
            ->innerJoin('u.applications', 'a')
            ->innerJoin('a.subevents', 's')
            ->where('per.name = :permission')
            ->andWhere('s.id = :sid')
            ->andWhere('a.validTo IS NULL')
            ->andWhere('(a.state = \'' . ApplicationState::PAID . '\' OR a.state = \'' . ApplicationState::PAID_FREE
                . '\' OR a.state = \'' . ApplicationState::WAITING_FOR_PAYMENT . '\')')
            ->setParameter('pid', $program->getId())
            ->setParameter('permission', Permission::CHOOSE_PROGRAMS)
            ->setParameter('sid', $program->getBlock()->getSubevent()->getId());

        if ($program->getBlock()->getCategory()) {
            $qb = $qb->innerJoin('r.registerableCategories', 'c')
                ->andWhere('c.id = :cid')
                ->setParameter('cid', $program->getBlock()->getCategory()->getId());
        }

        return new ArrayCollection($qb->getQuery()->getResult());
    }

    /**
     * Vrací uživatele jako možnosti pro select.
     * @return array
     */
    public function getUsersOptions()
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
     * @return array
     */
    public function getLectorsOptions()
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
     * @param User $user
     */
    public function save(User $user)
    {
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Odstraní externího uživatele.
     * @param User $user
     */
    public function remove(User $user)
    {
        foreach ($user->getCustomInputValues() as $customInputValue)
            $this->_em->remove($customInputValue);

        foreach ($user->getApplications() as $application)
            $this->_em->remove($application);

        foreach ($user->getLecturersBlocks() as $block)
            $block->setLector(NULL);

        $this->_em->remove($user);
        $this->_em->flush();
    }
}
