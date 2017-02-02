<?php

namespace App\Model\ACL;


use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;

class RoleRepository extends EntityRepository
{
    public function findRoleByName($name) {
        return $this->findOneBy(['name' => $name]);
    }

    public function findRoleByUntranslatedName($name) {
        return $this->findOneBy(['untranslatedName' => $name]);
    }

    public function findRegisterableRoles() {
        return $this->findBy(['registerable' => true]);
    }

    public function findRegisterableNowRoles()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('registerable', true))
            ->andWhere(Criteria::expr()->orX(Criteria::expr()->lte('registerableFrom', new \DateTime('now')), Criteria::expr()->isNull('registerableFrom')))
            ->andWhere(Criteria::expr()->orX(Criteria::expr()->gte('registerableTo', new \DateTime('now')), Criteria::expr()->isNull('registerableTo')));

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
            ->where(Criteria::expr()->neq('untranslatedName', Role::GUEST))
            ->andWhere(Criteria::expr()->neq('untranslatedName', Role::UNAPPROVED))
            ->andWhere(Criteria::expr()->neq('untranslatedName', Role::UNREGISTERED));

        return $this->matching($criteria);
    }

    public function findRolesWithoutGuestsOrderedByName() {
        $rolesWithoutGuests = $this->findRolesWithoutGuests();
        $criteria = Criteria::create()
            ->orderBy(['name' => 'ASC']);

        return $rolesWithoutGuests->matching($criteria);
    }

    public function findRolesByIds($ids) {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }
}