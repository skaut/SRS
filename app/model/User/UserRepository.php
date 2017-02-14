<?php

namespace App\Model\User;

use App\Model\ACL\Role;
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
    public function findAllInRoles($rolesIds) {
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
    public function findAllApprovedInRoles($rolesIds) {
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
     * @return array
     */
    public function getLectorsOptions() {
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
     * @param $variableSymbol
     * @return bool
     */
    public function variableSymbolExists($variableSymbol)
    {
        return $this->findOneBy(['variableSymbol' => $variableSymbol]) !== null;
    }

    /**
     * @param User $user
     */
    public function save(User $user) {
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

        //TODO odstraneni prihlasenych programu

        $this->_em->remove($user);
        $this->_em->flush();
    }

    public function setAttended($ids, $value = true) {
        $this->createQueryBuilder('u')
            ->update()
            ->set('u.attended', $value)
            ->where('u.id IN (:ids)')->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }
}