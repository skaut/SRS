<?php

namespace App\Model\ACL;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Query\QueryBuilder;
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
        $now = date("Y-m-d H:i");

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('registerable', true))
            ->andWhere(Criteria::expr()->orX(Criteria::expr()->lte('registerableFrom', new \DateTime('now')), Criteria::expr()->isNull('registerableFrom')))
            ->andWhere(Criteria::expr()->orX(Criteria::expr()->gte('registerableTo', new \DateTime('now')), Criteria::expr()->isNull('registerableTo')));

        return $this->matching($criteria);
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
}