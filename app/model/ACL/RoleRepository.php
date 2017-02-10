<?php

namespace App\Model\ACL;


use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;

class RoleRepository extends EntityRepository
{
    /**
     * @param $name
     * @return Role|null
     */
    public function findBySystemName($name) {
        return $this->findOneBy(['systemName' => $name]);
    }

    /**
     * @return array
     */
    public function findAllNames() {
        return $this->createQueryBuilder('r')
            ->select('r.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array
     */
    public function findAllRegisterable() {
        return $this->findBy(['registerable' => true]);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findAllRegisterableNow()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('registerable', true))
            ->andWhere(Criteria::expr()->orX(Criteria::expr()->lte('registerableFrom', new \DateTime()), Criteria::expr()->isNull('registerableFrom')))
            ->andWhere(Criteria::expr()->orX(Criteria::expr()->gte('registerableTo', new \DateTime()), Criteria::expr()->isNull('registerableTo')));

        return $this->matching($criteria);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findAllWithLimitedCapacity() {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->neq('capacity', null));
        return $this->matching($criteria);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findAllWithArrivalDeparture() {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('displayArrivalDeparture', true));
        return $this->matching($criteria);
    }

    /**
     * @param $ids
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findRolesByIds($ids) {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    /**
     * @param $roles
     * @return array
     */
    public function findRolesIds($roles) {
        return array_map(function($o) { return $o->getId(); }, $roles->toArray());
    }

    /**
     * @return array
     */
    public function getRolesOptions() {
        $roles = $this->createQueryBuilder('r')
            ->select('r.id, r.name')
            ->orderBy('r.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($roles as $role) {
            $options[$role['id']] = $role['name'];
        }
        return $options;
    }

    /**
     * @return array
     */
    public function getRolesWithoutGuestsOptions() {
        $roles = $this->createQueryBuilder('r')
            ->select('r.id, r.name')
            ->where('r.systemName != :guest')->setParameter('guest', Role::GUEST)
            ->andWhere('r.systemName != :unapproved')->setParameter('unapproved', Role::UNAPPROVED)
            ->andWhere('r.systemName != :nonregistered')->setParameter('nonregistered', Role::NONREGISTERED)
            ->orderBy('r.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($roles as $role) {
            $options[$role['id']] = $role['name'];
        }
        return $options;
    }
}