<?php

declare(strict_types=1);

namespace App\Model\ACL;

use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Translation\Translator;
use function array_map;

/**
 * Třída spravující role.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class RoleRepository extends EntityRepository
{
    /** @var Translator */
    private $translator;


    public function injectTranslator(Translator $translator) : void
    {
        $this->translator = $translator;
    }

    /**
     * Vrací roli podle id.
     */
    public function findById(?int $id) : ?Role
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací roli podle názvu.
     */
    public function findByName(string $name) : ?Role
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * Vrací systémovou roli podle systémového názvu.
     */
    public function findBySystemName(string $name) : ?Role
    {
        return $this->findOneBy(['systemName' => $name]);
    }

    /**
     * Vrací id naposledy přidané role.
     * @throws NonUniqueResultException
     */
    public function findLastId() : ?int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('MAX(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací názvy všech rolí.
     * @return string[]
     */
    public function findAllNames() : array
    {
        $names = $this->createQueryBuilder('r')
            ->select('r.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * Vrací názvy rolí, kromě role se zadaným id.
     * @return string[]
     */
    public function findOthersNames(int $id) : array
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
     * @return Role[]
     */
    public function findAllRegisterable() : array
    {
        return $this->findBy(['registerable' => true]);
    }

    /**
     * Vrací role s omezenou kapacitou.
     * @return Collection|Role[]
     */
    public function findAllWithLimitedCapacity() : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->neq('capacity', null));
        return $this->matching($criteria);
    }

    /**
     * Vrací role, u kterých se eviduje příjezd a odjezd.
     * @return Collection|Role[]
     */
    public function findAllWithArrivalDeparture() : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('displayArrivalDeparture', true));
        return $this->matching($criteria);
    }

    /**
     * Vrací role, u kterých je cena počítána podle podakcí.
     * @return Collection|Role[]
     */
    public function findAllWithSubevents() : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('fee', null));
        return $this->matching($criteria);
    }

    /**
     * Vrací role podle id.
     * @param int[] $ids
     * @return Collection|Role[]
     */
    public function findRolesByIds(array $ids) : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    /**
     * Vrací role s počty uživatelů.
     * @param int[] $rolesIds
     * @return string[][]
     */
    public function countUsersInRoles(array $rolesIds) : array
    {
        return $this->createQueryBuilder('r')
            ->select('r.name, r.capacity, COUNT(u.id) AS usersCount')
            ->leftJoin('r.users', 'u')
            ->where('r.id IN (:ids)')->setParameter('ids', $rolesIds)
            ->groupBy('r.id')
            ->orderBy('r.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací id rolí.
     * @param Collection|Role[] $roles
     * @return int[]
     */
    public function findRolesIds(Collection $roles) : array
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $roles->toArray());
    }

    /**
     * Vrací seznam rolí jako možnosti pro select, role specifikovaná parametrem je vynechána.
     * @return string[]
     */
    public function getRolesWithoutRoleOptions(int $roleId) : array
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
     * @return string[]
     */
    public function getRegisterableNowOptionsWithCapacity() : array
    {
        $roles = $this->findAllRegisterableNowOrderedByName();

        $options = [];
        foreach ($roles as $role) {
            if ($role->hasLimitedCapacity()) {
                $options[$role->getId()] = $this->translator->translate('web.common.role_option', null, [
                    'role' => $role->getName(),
                    'occupied' => $role->countUsers(),
                    'total' => $role->getCapacity(),
                ]);
            } else {
                $options[$role->getId()] = $role->getName();
            }
        }
        return $options;
    }

    /**
     * Vraci role, ktere jsou tuto chvíli registrovatelné, seřazené podle názvu.
     * @return Collection|Role[]
     */
    public function findAllRegisterableNowOrderedByName() : Collection
    {
        $result = $this->createQueryBuilder('r')
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

        return new ArrayCollection($result);
    }

    /**
     * Vrací seznam rolí, které jsou v tuto chvíli registrovatelné nebo je uživatel má, s informací o jejich
     * obsazenosti, jako možnosti pro select.
     * @return Role[]
     */
    public function getRegisterableNowOrUsersOptionsWithCapacity(User $user) : array
    {
        $roles = $this->findAllRegisterableNowOrUsersOrderedByName($user);

        $options = [];
        foreach ($roles as $role) {
            if ($role->hasLimitedCapacity()) {
                $options[$role->getId()] = $this->translator->translate('web.common.role_option', null, [
                    'role' => $role->getName(),
                    'occupied' => $role->countUsers(),
                    'total' => $role->getCapacity(),
                ]);
            } else {
                $options[$role->getId()] = $role->getName();
            }
        }
        return $options;
    }

    /**
     * Vrací role, které jsou v tuto chvíli registrovatelné nebo je uživatel má, seřazené podle názvu.
     * @return Role[]
     */
    public function findAllRegisterableNowOrUsersOrderedByName(User $user) : array
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
     * Vrací role bez vybraných rolí jako možnosti pro select.
     * @param string[] $withoutRoles
     * @return string[]
     */
    public function getRolesWithoutRolesOptions(array $withoutRoles) : array
    {
        if (empty($withoutRoles)) {
            $roles = $this->createQueryBuilder('r')
                ->select('r.id, r.name')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        } else {
            $roles = $this->createQueryBuilder('r')
                ->select('r.id, r.name')
                ->where('r.systemName NOT IN (:roles)')->setParameter('roles', $withoutRoles)
                ->orWhere('r.systemName IS NULL')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        }

        $options = [];
        foreach ($roles as $role) {
            $options[$role['id']] = $role['name'];
        }
        return $options;
    }

    /**
     * Vrací seznam rolí bez vybraných rolí, s informací o obsazenosti, jako možnosti pro select.
     * @param string[] $withoutRoles
     * @return string[]
     */
    public function getRolesWithoutRolesOptionsWithCapacity(array $withoutRoles) : array
    {
        if (empty($withoutRoles)) {
            $roles = $this->createQueryBuilder('r')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        } else {
            $roles = $this->createQueryBuilder('r')
                ->where('r.systemName NOT IN (:roles)')->setParameter('roles', $withoutRoles)
                ->orWhere('r.systemName IS NULL')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        }

        $options = [];
        foreach ($roles as $role) {
            if ($role->hasLimitedCapacity()) {
                $options[$role->getId()] = $this->translator->translate('web.common.role_option', null, [
                    'role' => $role->getName(),
                    'occupied' => $role->countUsers(),
                    'total' => $role->getCapacity(),
                ]);
            } else {
                $options[$role->getId()] = $role->getName();
            }
        }
        return $options;
    }

    /**
     * Vrací seznam rolí bez vybraných rolí, s informací o počtu uživatelů, jako možnosti pro select.
     * @param string[] $withoutRoles
     * @return string[]
     */
    public function getRolesWithoutRolesOptionsWithApprovedUsersCount(array $withoutRoles) : array
    {
        if (empty($withoutRoles)) {
            $roles = $this->createQueryBuilder('r')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        } else {
            $roles = $this->createQueryBuilder('r')
                ->where('r.systemName NOT IN (:roles)')->setParameter('roles', $withoutRoles)
                ->orWhere('r.systemName IS NULL')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        }

        $options = [];
        foreach ($roles as $role) {
            $options[$role->getId()] = $this->translator->translate(
                'admin.common.role_option',
                $role->countUsers(),
                [
                    'role' => $role->getName(),
                ]
            );
        }
        return $options;
    }

    /**
     * Uloží roli.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Role $role) : void
    {
        $this->_em->persist($role);
        $this->_em->flush();
    }

    /**
     * Odstraní roli.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Role $role) : void
    {
        $this->_em->remove($role);
        $this->_em->flush();
    }

    public function incrementOccupancy(Role $role) : void
    {
        $this->createQuery('UPDATE App\Model\ACL\Role r SET r.occupancy = r.occupancy + 1 WHERE r.id = :rid')
            ->setParameter('rid', $role->getId())
            ->getResult();
    }

    public function decrementOccupancy(Role $role) : void
    {
        $this->createQuery('UPDATE App\Model\ACL\Role r SET r.occupancy = r.occupancy - 1 WHERE r.id = :rid')
            ->setParameter('rid', $role->getId())
            ->getResult();
    }

    public function getRegistrationStart() : ?\DateTime
    {
        $result = $this->createQueryBuilder('r')
            ->select('r.registerableFrom')
            ->where('r.registerable = TRUE')
            ->andWhere('r.registerableFrom IS NOT NULL')
            ->orderBy('r.registerableFrom')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        return $result ? $result['registerableFrom'] : null;
    }

    public function getRegistrationEnd() : ?\DateTime
    {
        $result = $this->createQueryBuilder('r')
            ->select('r.registerableTo')
            ->where('r.registerable = TRUE')
            ->andWhere('r.registerableTo IS NOT NULL')
            ->orderBy('r.registerableTo', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        return $result ? $result['registerableTo'] : null;
    }
}
