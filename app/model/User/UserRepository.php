<?php

namespace App\Model\User;

use App\Model\ACL\Permission;
use App\Model\ACL\Role;
use App\Model\Program\Program;
use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;


class UserRepository extends EntityRepository
{
    /**
     * @param $id
     * @return User|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param $skautISUserId
     * @return User|null
     */
    public function findBySkautISUserId($skautISUserId)
    {
        return $this->findOneBy(['skautISUserId' => $skautISUserId]);
    }

    /**
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
     * @param $variableSymbolCode
     */
    public function setVariableSymbolCode($variableSymbolCode)
    {
        $this->createQueryBuilder('u')
            ->update()
            ->set('u.variableSymbol', $this->createQueryBuilder()->expr()->concat(
                ':code',
                $this->createQueryBuilder()->expr()->substring('u.variableSymbol', 3, 6)
            ))
            ->setParameter('code', $variableSymbolCode)
            ->where('u.variableSymbol NOT LIKE :edited')
            ->setParameter('edited', '%#')
            ->getQuery()
            ->execute();
    }

    /**
     * @param User $user
     */
    public function save(User $user)
    {
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * @param User $user
     */
    public function remove(User $user)
    {
        foreach ($user->getCustomInputValues() as $customInputValue)
            $this->_em->remove($customInputValue);

        $this->_em->remove($user);
        $this->_em->flush();
    }

    /**
     * @param $ids
     * @param bool $value
     */
    public function setAttended($ids, $value = true)
    {
        $this->createQueryBuilder('u')
            ->update()
            ->set('u.attended', $value)
            ->where('u.id IN (:ids)')->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }
}