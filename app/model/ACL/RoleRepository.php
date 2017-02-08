<?php

namespace App\Model\ACL;


use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;

class RoleRepository extends EntityRepository
{
    public function findRolesNames() {
        return $this->createQueryBuilder('r')->select('r.name')->getQuery()->execute();
    }

    public function findRoleByName($name) {
        return $this->findOneBy(['name' => $name]);
    }

    public function findRoleBySystemName($name) {
        return $this->findOneBy(['systemName' => $name]);
    }

    public function findRegisterableRoles() {
        return $this->findBy(['registerable' => true]);
    }

    public function findRegisterableNowRoles()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('registerable', true))
            ->andWhere(Criteria::expr()->orX(Criteria::expr()->lte('registerableFrom', new \DateTime()), Criteria::expr()->isNull('registerableFrom')))
            ->andWhere(Criteria::expr()->orX(Criteria::expr()->gte('registerableTo', new \DateTime()), Criteria::expr()->isNull('registerableTo')));

        return $this->matching($criteria);
    }

    public function findRegisterableNowRolesOrderedByName()
    {
        $registerableNowRoles = $this->findRegisterableNowRoles();
        $criteria = Criteria::create()
            ->orderBy(['name' => 'ASC']);

        return $registerableNowRoles->matching($criteria);
    }

    public function findRolesWithLimitedCapacity() {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->neq('capacity', null));
        return $this->matching($criteria);
    }

    public function findRolesWithArrivalDeparture() {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('displayArrivalDeparture', true));
        return $this->matching($criteria);
    }

    public function findRolesOrderedByName() {
        $criteria = Criteria::create()
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    public function findRolesWithoutGuests() {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->neq('systemName', Role::GUEST))
            ->andWhere(Criteria::expr()->neq('systemName', Role::UNAPPROVED))
            ->andWhere(Criteria::expr()->neq('systemName', Role::NONREGISTERED));

        return $this->matching($criteria);
    }

    public function findRolesByIds($ids) {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    public function getRolesWithoutGuestsOptions() {
        $roles = $this->createQueryBuilder('r')
            ->select('r.id, r.name')
            ->where('r.systemName != :guest')->setParameter('guest', Role::GUEST)
            ->andWhere('r.systemName != :guest')->setParameter('guest', Role::UNAPPROVED)
            ->andWhere('r.systemName != :guest')->setParameter('guest', Role::NONREGISTERED)
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