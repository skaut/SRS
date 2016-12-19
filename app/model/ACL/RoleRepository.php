<?php

namespace App\Model\ACL;

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
        return $this->findOneBy(array('name' => $name));
    }

    public function findRoleByUntranslatedName($name) {
        return $this->findRoleByName($this->translator->translate('common.role.' . $name));
    }

    public function findRegisterable() {
        $query = $this->_em->createQuery("SELECT r FROM {Role::class} r WHERE r.registerable=true");
        return $query->getResult();
    }

    public function findRegisterableNow()
    {
        $today = date("Y-m-d H:i");

        $query = $this->_em->createQuery("SELECT r FROM {Role::class} r WHERE r.registerable=true
              AND (r.registerableFrom <= '{$today}' OR r.registerableFrom IS NULL)
              AND (r.registerableTo >= '{$today}' OR r.registerableTo IS NULL)");
        return $query->getResult();
    }

    public function findCapacityLimitedRoles() {
        $query = $this->_em->createQuery("SELECT r FROM {Role::class} r WHERE r.capacity IS NOT NULL");
        return $query->getResult();
    }

    public function findArrivalDepartureVisibleRoles() {
        $query = $this->_em->createQuery("SELECT r FROM {Role::class} r WHERE r.displayArrivalDeparture = 1");
        return $query->getResult();
    }

    public function findApprovedUsersInRole($role)
    {
        $query = $this->_em->createQuery("SELECT u FROM User u JOIN u.roles r WHERE u.approved=true AND r.name='$role'");
        return $query->getResult();
    }
}