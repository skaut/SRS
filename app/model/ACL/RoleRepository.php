<?php

namespace App\Model\ACL;

use App\Model\User\User;
use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Translation\Translator;


/**
 * Třída spravující role.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class RoleRepository extends EntityRepository
{
    /** @var Translator */
    private $translator;


    /**
     * @param Translator $translator
     */
    public function injectTranslator(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Vrací roli podle id.
     * @param $id
     * @return Role|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací roli podle názvu.
     * @param $name
     * @return Role|null
     */
    public function findByName($name)
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * Vrací systémovou roli podle systémového názvu.
     * @param $name
     * @return Role|null
     */
    public function findBySystemName($name)
    {
        return $this->findOneBy(['systemName' => $name]);
    }

    /**
     * Vrací id naposledy přidané role.
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
     * Vrací názvy všech rolí.
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
     * Vrací názvy rolí, kromě role se zadaným id.
     * @param $id
     * @return array
     */
    public function findOthersNames($id)
    {
        $names = $this->createQueryBuilder('r')
            ->select('r.name')
            ->where('r.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * Vrací registrovatelné role.
     * @return array
     */
    public function findAllRegisterable()
    {
        return $this->findBy(['registerable' => TRUE]);
    }

    /**
     * Vrací role s omezenou kapacitou.
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findAllWithLimitedCapacity()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->neq('capacity', NULL));
        return $this->matching($criteria);
    }

    /**
     * Vrací role, u kterých se eviduje příjezd a odjezd.
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findAllWithArrivalDeparture()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('displayArrivalDeparture', TRUE));
        return $this->matching($criteria);
    }

    /**
     * Vrací role podle id.
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
     * Vrací počet volných míst v roli nebo null u rolí s neomezenou kapacitou.
     * @param Role $role
     * @return int|null
     */
    public function countUnoccupiedInRole(Role $role)
    {
        if ($role->getCapacity() === NULL)
            return NULL;
        return $role->getCapacity() - $this->countApprovedUsersInRole($role);
    }

    /**
     * Vrací počet volných míst v rolích nebo null u rolí s neomezenou kapacitou.
     * @param $roles
     * @return array
     */
    public function countUnoccupiedInRoles($roles)
    {
        $counts = [];
        foreach ($roles as $role) {
            $counts[$role->getId()] = $this->countUnoccupiedInRole($role);
        }
        return $counts;
    }

    /**
     * Vrací počet schválených uživatelů v roli.
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
     * Vrací počet schválených uživatelů v rolích.
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
     * Vrací id rolí.
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
     * Vrací seznam rolí jako možnosti pro select, role specifikovaná parametrem je vynechána.
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
     * Vrací seznam rolí s obsazenostmi jako možnosti pro select.
     * @return array
     */
    public function getRegisterableNowOptionsWithCapacity()
    {
        $roles = $this->findAllRegisterableNowOrderedByName();

        $options = [];
        foreach ($roles as $role) {
            if ($role->hasLimitedCapacity())
                $options[$role->getId()] = $this->translator->translate('web.common.role_option', NULL, [
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
     * Vraci role, ktere jsou tuto chvíli registrovatelné, seřazené podle názvu.
     * @return array
     */
    public function findAllRegisterableNowOrderedByName()
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->where($this->createQueryBuilder()->expr()->andX(
                $this->createQueryBuilder()->expr()->eq('r.registerable', TRUE),
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
     * Vrací seznam rolí, které jsou v tuto chvíli registrovatelné nebo je uživatel má, s informací o jejich
     * obsazenosti, jako možnosti pro select.
     * @param User $user
     * @return array
     */
    public function getRegisterableNowOrUsersOptionsWithCapacity(User $user)
    {
        $roles = $this->findAllRegisterableNowOrUsersOrderedByName($user);

        $options = [];
        foreach ($roles as $role) {
            if ($role->hasLimitedCapacity())
                $options[$role->getId()] = $this->translator->translate('web.common.role_option', NULL, [
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
     * Vrací role, které jsou v tuto chvíli registrovatelné nebo je uživatel má, seřazené podle názvu.
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
                    $this->createQueryBuilder()->expr()->eq('r.registerable', TRUE),
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
     * Vrací role bez vybraných rolí jako možnosti pro select.
     * @param array $withoutRoles
     * @return array
     */
    public function getRolesWithoutRolesOptions(array $withoutRoles)
    {
        if (empty($withoutRoles))
            $roles = $this->createQueryBuilder('r')
                ->select('r.id, r.name')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        else
            $roles = $this->createQueryBuilder('r')
                ->select('r.id, r.name')
                ->where('r.systemName NOT IN (:roles)')->setParameter('roles', $withoutRoles)
                ->orWhere('r.systemName IS NULL')
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
     * Vrací seznam rolí bez vybraných rolí, s informací o obsazenosti, jako možnosti pro select.
     * @param array $withoutRoles
     * @return array
     */
    public function getRolesWithoutRolesOptionsWithCapacity(array $withoutRoles)
    {
        if (empty($withoutRoles))
            $roles = $this->createQueryBuilder('r')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        else
            $roles = $this->createQueryBuilder('r')
                ->where('r.systemName NOT IN (:roles)')->setParameter('roles', $withoutRoles)
                ->orWhere('r.systemName IS NULL')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();

        $options = [];
        foreach ($roles as $role) {
            if ($role->hasLimitedCapacity())
                $options[$role->getId()] = $this->translator->translate('web.common.role_option', NULL, [
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
     * Vrací seznam rolí bez vybraných rolí, s informací o počtu uživatelů, jako možnosti pro select.
     * @param array $withoutRoles
     * @return array
     */
    public function getRolesWithoutRolesOptionsWithUsersCount(array $withoutRoles)
    {
        if (empty($withoutRoles))
            $roles = $this->createQueryBuilder('r')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        else
            $roles = $this->createQueryBuilder('r')
                ->where('r.systemName NOT IN (:roles)')->setParameter('roles', $withoutRoles)
                ->orWhere('r.systemName IS NULL')
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

    /**
     * Uloží roli.
     * @param Role $role
     */
    public function save(Role $role)
    {
        $this->_em->persist($role);
        $this->_em->flush();
    }

    /**
     * Odstraní roli.
     * @param Role $role
     */
    public function remove(Role $role)
    {
        $this->_em->remove($role);
        $this->_em->flush();
    }
}
