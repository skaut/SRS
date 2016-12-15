<?php

namespace App\Model\ACL;

use Nette;

class RoleRepository extends Nette\Object
{
    private $em;
    private $roleRepository;

    public function __construct(\Kdyby\Doctrine\EntityManager $em)
    {
        $this->em = $em;
        $this->roleRepository = $em->getRepository(Role::class);
    }

    public function findRegisterable() {
        $query = $this->em->createQuery("SELECT r FROM {Role::class} r WHERE r.registerable=true");
        return $query->getResult();
    }

    public function findRegisterableNow()
    {
        $today = date("Y-m-d H:i");

        $query = $this->em->createQuery("SELECT r FROM {Role::class} r WHERE r.registerable=true
              AND (r.registerableFrom <= '{$today}' OR r.registerableFrom IS NULL)
              AND (r.registerableTo >= '{$today}' OR r.registerableTo IS NULL)");
        return $query->getResult();
    }

    public function findCapacityLimitedRoles() {
        $query = $this->_em->createQuery("SELECT r FROM {Role::class} r WHERE r.usersLimit IS NOT NULL");
        return $query->getResult();
    }

    public function findCapacityVisibleRoles() {
        $query = $this->_em->createQuery("SELECT r FROM {Role::class} r WHERE r.displayCapacity = 1");
        return $query->getResult();
    }

    public function findArrivalDepartureVisibleRoles() {
        $query = $this->_em->createQuery("SELECT r FROM {Role::class} r WHERE r.displayArrivalDeparture = 1");
        return $query->getResult();
    }

    public function findApprovedUsersInRole($roleName)
    {
        $query = $this->_em->createQuery("SELECT u FROM User u JOIN u.roles r WHERE u.approved=true AND r.name='$roleName'");
        return $query->getResult();
    }
}