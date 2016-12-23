<?php

namespace App\Model\ACL;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Query\QueryBuilder;
use Kdyby\Doctrine\EntityRepository;

class RoleRepository extends EntityRepository
{
    /**
     * @var \Kdyby\Translation\Translator
     */
    protected $translator;

    public function __construct(\Kdyby\Doctrine\EntityManager $em, \Doctrine\ORM\Mapping\ClassMetadata $metadata, \Kdyby\Translation\Translator $translator)
    {
        parent::__construct($em, $metadata);
        $this->translator = $translator;
    }

    public function findRoleByName($name) {
        return $this->findOneBy(['name' => $name]);
    }

    public function findRoleByUntranslatedName($name) {
        return $this->findRoleByName($this->translator->translate('common.role.' . $name));
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