<?php

namespace App\Model\User;

use App\Model\ACL\Role;
use Kdyby\Doctrine\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findUserById($id)
    {
        return $this->find($id);
    }

    public function findUserBySkautISUserId($skautISUserId)
    {
        return $this->findOneBy(['skautISUserId' => $skautISUserId]);
    }

    public function findUsersForSync()
    {
        return $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.syncedWithSkautIS = true')
            ->getQuery()->execute();
    }

    public function findApprovedUsersInRole($systemName)
    {
        return $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.systemName = :name')->setParameter('name', $systemName)
            ->andWhere('u.approved = true')
            ->orderBy('u.displayName')
            ->getQuery()->execute();
    }

    public function findApprovedUsersInRoles($rolesIds) {
        return $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.id IN (:ids)')->setParameter('ids', $rolesIds)
            ->andWhere('u.approved = true')
            ->orderBy('u.displayName')
            ->getQuery()->execute();
    }

    public function getLectorsOptions() {
        $lectors = $this->createQueryBuilder('u')
            ->select('u.id, u.displayName')
            ->join('u.roles', 'r')
            ->where('r.systemName = :name')->setParameter('name', Role::LECTOR)
            ->andWhere('u.approved = true')
            ->orderBy('u.displayName')
            ->getQuery()->execute();

        $options = [];
        foreach ($lectors as $lector) {
            $options[$lector['id']] = $lector['displayName'];
        }
        return $options;
    }

    public function variableSymbolExists($variableSymbol)
    {
        return $this->findOneBy(['variableSymbol' => $variableSymbol]) !== null;
    }

    public function removeUser($user)
    {
        $this->_em->remove($user);
        $this->_em->flush();
    }
}