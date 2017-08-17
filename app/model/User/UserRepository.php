<?php

namespace App\Model\User;

use App\Model\ACL\Permission;
use App\Model\ACL\Role;
use App\Model\Program\Program;
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
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findUsersByIds($ids)
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
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací schválené uživatele v roli.
     * @param $systemName
     * @return mixed
     */
    public function findAllApprovedInRole($systemName)
    {
        return $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.systemName = :name')->setParameter('name', $systemName)
            ->andWhere('u.approved = true')
            ->orderBy('u.displayName')
            ->getQuery()->execute();
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
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací programy, na které se uživatel může přihlásit.
     * @param $program
     * @return array
     */
    public function findProgramAllowed(Program $program)
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.programs', 'p', 'WITH', 'p.id = :pid')
            ->innerJoin('u.roles', 'r')
            ->innerJoin('r.permissions', 'per')
            ->where('per.name = :permission')
            ->setParameter('pid', $program->getId())
            ->setParameter('permission', Permission::CHOOSE_PROGRAMS);

        if ($program->getBlock()->getCategory()) {
            $qb = $qb->innerJoin('r.registerableCategories', 'c')
                ->andWhere('c.id = :cid')
                ->setParameter('cid', $program->getBlock()->getCategory()->getId());
        }

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * Vrací uživatele jako možnosti pro select.
     * @return array
     */
    public function getUsersOptions()
    {
        $options = [];
        foreach ($this->findAll() as $user) {
            $options[$user->getId()] = $user->getDisplayName();
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
     * Vrací kategorie, ze kterých si uživatel může vybírat programy.
     * @param User $user
     * @return int[]
     */
    public function findRegisterableCategoriesIdsByUser(User $user)
    {
        return $this->createQueryBuilder('u')
            ->select('c.id')
            ->join('u.roles', 'r')
            ->join('r.registerableCategories', 'c')
            ->where('u.id = :id')->setParameter('id', $user->getId())
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * Vrací pořadí poslední odeslané přihlášky.
     * @return int
     */
    public function findLastApplicationOrder()
    {
        return $this->createQueryBuilder('u')
            ->select('MAX(u.applicationOrder)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací true, pokud existuje uživatel s tímto variabilním symbolem.
     * @param $variableSymbol
     * @return bool
     */
    public function variableSymbolExists($variableSymbol)
    {
        $res = $this->createQueryBuilder('u')
            ->where('u.variableSymbol LIKE :vs')->setParameter('vs', $variableSymbol . '%')
            ->getQuery()
            ->getResult();
        return !empty($res);
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
     * Odstraní uživatele.
     * @param User $user
     */
    public function remove(User $user)
    {
        foreach ($user->getCustomInputValues() as $customInputValue)
            $this->_em->remove($customInputValue);

        foreach ($user->getLecturersBlocks() as $block)
            $block->setLector(NULL);

        $this->_em->remove($user);
        $this->_em->flush();
    }

    /**
     * Nastaví uživatelům účast.
     * @param $ids
     * @param bool $value
     */
    public function setAttended($ids, $value = TRUE)
    {
        $this->createQueryBuilder('u')
            ->update()
            ->set('u.attended', $value)
            ->where('u.id IN (:ids)')->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }
}
