<?php

namespace App\Model\ACL;


use App\Model\User\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Translation\Translator;

class RoleRepository extends EntityRepository
{
    /** @var Translator */
    private $translator;

    public function __construct(EntityManager $em, Mapping\ClassMetadata $class, Translator $translator)
    {
        parent::__construct($em, $class);
        $this->translator = $translator;
    }

    /**
     * @param $id
     * @return Role|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param $name
     * @return Role|null
     */
    public function findByName($name)
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * @param $name
     * @return Role|null
     */
    public function findBySystemName($name)
    {
        return $this->findOneBy(['systemName' => $name]);
    }

    /**
     * @return int
     */
    public function findLastId()
    {
        return $this->createQueryBuilder('r')
            ->select('MAX(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return string[]
     */
    public function findAllNames()
    {
        $names = $this->createQueryBuilder('r')
            ->select('r.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * @param $id
     * @return array
     */
    public function findOthersNames($id) {
        $names = $this->createQueryBuilder('b')
            ->select('b.name')
            ->where('b.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * Vraci vsechny registrovatelne role.
     *
     * @return array
     */
    public function findAllRegisterable()
    {
        return $this->findBy(['registerable' => true]);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findAllWithLimitedCapacity()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->neq('capacity', null));
        return $this->matching($criteria);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findAllWithArrivalDeparture()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('displayArrivalDeparture', true));
        return $this->matching($criteria);
    }

    /**
     * @param $ids
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findRolesByIds($ids)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    /**
     * @param Role $role
     * @return int|null
     */
    public function countUnoccupiedInRole(Role $role)
    {
        if ($role->getCapacity() === null)
            return null;
        return $role->getCapacity() - $this->countApprovedUsersInRole($role);
    }

    /**
     * @param $roles
     * @return array
     */
    public function countUnoccupiedInRoles($roles) {
        $count = [];
        foreach ($roles as $role) {
            $count[$role->getId()] = $this->countUnoccupiedInRole($role);
        }
        return $count;
    }

    /**
     * @param Role $role
     * @return int
     */
    public function countApprovedUsersInRole(Role $role)
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(u.id)')
            ->leftJoin('r.users', 'u', 'WITH', 'u.approved = true')
            ->where('r.id = :id')->setParameter('id', $role->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param $roles
     * @return array
     */
    public function countApprovedUsersInRoles($roles)
    {
        return $this->createQueryBuilder('r')
            ->select('r.name, r.capacity', 'COUNT(u.id) AS usersCount')
            ->leftJoin('r.users', 'u', 'WITH', 'u.approved = true')
            ->where('r.id IN (:ids)')->setParameter('ids', $this->findRolesIds($roles))
            ->groupBy('r.id')
            ->orderBy('r.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $roles
     * @return array
     */
    public function findRolesIds($roles)
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $roles->toArray());
    }

    /**
     * @param $roleId
     * @return array
     */
    public function getRolesWithoutRoleOptions($roleId)
    {
        $roles = $this->createQueryBuilder('r')
            ->select('r.id, r.name')
            ->where('r.id != :id')->setParameter('id', $roleId)
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
    public function getRegisterableNowOptionsWithCapacity()
    {
        $roles = $this->findAllRegisterableNowOrderedByName();

        $options = [];
        foreach ($roles as $role) {
            if ($role->hasLimitedCapacity())
                $options[$role->getId()] = $this->translator->translate('web.common.role_option', null, [
                    'role' => $role->getName(),
                    'occupied' => $this->countApprovedUsersInRole($role),
                    'total' => $role->getCapacity()
                ]);
            else
                $options[$role->getId()] = $role->getName();
        }
        return $options;
    }

    /**
     * Vraci role, ktere jsou aktualne registrovatelne, serazene podle nazvu.
     *
     * @return array
     */
    public function findAllRegisterableNowOrderedByName()
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->where($this->createQueryBuilder()->expr()->andX(
                $this->createQueryBuilder()->expr()->eq('r.registerable', true),
                $this->createQueryBuilder()->expr()->orX(
                    $this->createQueryBuilder()->expr()->lte('r.registerableFrom', 'CURRENT_TIMESTAMP()'),
                    $this->createQueryBuilder()->expr()->isNull('r.registerableFrom')
                ),
                $this->createQueryBuilder()->expr()->orX(
                    $this->createQueryBuilder()->expr()->gte('r.registerableTo', 'CURRENT_TIMESTAMP()'),
                    $this->createQueryBuilder()->expr()->isNull('r.registerableTo')
                )
            ))
            ->orderBy('r.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User $user
     * @return array
     */
    public function getRegisterableNowOrUsersOptionsWithCapacity(User $user)
    {
        $roles = $this->findAllRegisterableNowOrUsersOrderedByName($user);

        $options = [];
        foreach ($roles as $role) {
            if ($role->hasLimitedCapacity())
                $options[$role->getId()] = $this->translator->translate('web.common.role_option', null, [
                    'role' => $role->getName(),
                    'occupied' => $this->countApprovedUsersInRole($role),
                    'total' => $role->getCapacity()
                ]);
            else
                $options[$role->getId()] = $role->getName();
        }
        return $options;
    }

    /**
     * Vraci role, ktere jsou aktualne registrovatelne nebo uz je uzivatel ma, serazene podle nazvu.
     *
     * @param User $user
     * @return array
     */
    public function findAllRegisterableNowOrUsersOrderedByName(User $user)
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->leftJoin('r.users', 'u')
            ->where($this->createQueryBuilder()->expr()->orX(
                $this->createQueryBuilder()->expr()->andX(
                    $this->createQueryBuilder()->expr()->eq('r.registerable', true),
                    $this->createQueryBuilder()->expr()->orX(
                        $this->createQueryBuilder()->expr()->lte('r.registerableFrom', 'CURRENT_TIMESTAMP()'),
                        $this->createQueryBuilder()->expr()->isNull('r.registerableFrom')
                    ),
                    $this->createQueryBuilder()->expr()->orX(
                        $this->createQueryBuilder()->expr()->gte('r.registerableTo', 'CURRENT_TIMESTAMP()'),
                        $this->createQueryBuilder()->expr()->isNull('r.registerableTo')
                    )
                ),
                $this->createQueryBuilder()->expr()->eq('u.id', $user->getId())
            ))
            ->orderBy('r.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array $withoutRoles
     * @return array
     */
    public function getRolesWithoutRolesOptions(array $withoutRoles)
    {
        $roles = $this->createQueryBuilder('r')
            ->select('r.id, r.name')
            ->where('r.systemName NOT IN (:roles)')->setParameter('roles', $withoutRoles)
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
    public function getRolesWithoutRolesOptionsWithCapacity(array $withoutRoles)
    {
        $roles = $this->createQueryBuilder('r')
            ->where('r.systemName NOT IN (:roles)')->setParameter('roles', $withoutRoles)
            ->orderBy('r.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($roles as $role) {
            if ($role->hasLimitedCapacity())
                $options[$role->getId()] = $this->translator->translate('web.common.role_option', null, [
                    'role' => $role->getName(),
                    'occupied' => $this->countApprovedUsersInRole($role),
                    'total' => $role->getCapacity()
                ]);
            else
                $options[$role->getId()] = $role->getName();
        }
        return $options;
    }

    /**
     * @param array $withoutRoles
     * @return array
     */
    public function getRolesWithoutRolesOptionsWithUsersCount(array $withoutRoles)
    {
        $roles = $this->createQueryBuilder('r')
            ->where('r.systemName NOT IN (:roles)')->setParameter('roles', $withoutRoles)
            ->orderBy('r.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($roles as $role) {
            $options[$role->getId()] = $this->translator->translate('admin.common.role_option',
                $this->countApprovedUsersInRole($role), [
                    'role' => $role->getName()
                ]
            );
        }
        return $options;
    }

    public function save(Role $role) {
        $this->_em->persist($role);
        $this->_em->flush();
    }

    public function remove(Role $role) {
        $this->_em->remove($role);
        $this->_em->flush();
    }



}