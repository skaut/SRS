<?php

declare(strict_types=1);

namespace App\Model\Acl;

use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use function array_map;

/**
 * Třída spravující role.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class RoleRepository extends EntityRepository
{
    /**
     * Vrací roli podle id.
     */
    public function findById(?int $id) : ?Role
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací systémovou roli podle systémového názvu.
     */
    public function findBySystemName(string $name) : Role
    {
        return $this->findOneBy(['systemName' => $name]);
    }

    /**
     * Vrací id naposledy přidané role.
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findLastId() : ?int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('MAX(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací názvy rolí, kromě role se zadaným id.
     *
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
     * Vrací role podle id.
     *
     * @param int[] $ids
     *
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
     *
     * @param int[] $rolesIds
     *
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
     *
     * @param Collection|Role[] $roles
     *
     * @return int[]
     */
    public function findRolesIds(Collection $roles) : array
    {
        return array_map(static function ($o) {
            return $o->getId();
        }, $roles->toArray());
    }

    /**
     * Vrací role splňující podmínku seřazené podle názvu.
     *
     * @return Collection|Role[]
     */
    public function findFilteredRoles(bool $registerableNowOnly, bool $subeventsRoleOnly, bool $arrivalDepartureOnly, bool $includeUsers, ?User $user = null) : Collection
    {
        $qb = $this->createQueryBuilder('r');

        $query = $qb
            ->select('r')
            ->where('1 = 1');

        if ($registerableNowOnly) {
            $query = $query
                ->andWhere($qb->expr()->eq('r.registerable', true))
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->lte('r.registerableFrom', 'CURRENT_TIMESTAMP()'),
                    $qb->expr()->isNull('r.registerableFrom')
                ))
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->gte('r.registerableTo', 'CURRENT_TIMESTAMP()'),
                    $qb->expr()->isNull('r.registerableTo')
                ));
        }

        if ($subeventsRoleOnly) {
            $query = $query->andWhere('r.fee IS NULL');
        }

        if ($arrivalDepartureOnly) {
            $query = $query->andWhere('r.displayArrivalDeparture = TRUE');
        }

        if ($includeUsers) {
            $query = $query->orWhere('r in (:users_roles)')->setParameter('users_roles', $user->getRoles());
        }

        $result = $query
            ->orderBy('r.name')
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    public function incrementOccupancy(Role $role) : void
    {
        $this->_em->createQuery('UPDATE App\Model\Acl\Role r SET r.occupancy = r.occupancy + 1 WHERE r.id = :rid')
            ->setParameter('rid', $role->getId())
            ->getResult();
    }

    public function decrementOccupancy(Role $role) : void
    {
        $this->_em->createQuery('UPDATE App\Model\Acl\Role r SET r.occupancy = r.occupancy - 1 WHERE r.id = :rid')
            ->setParameter('rid', $role->getId())
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getRegistrationStart() : ?DateTimeImmutable
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

    /**
     * @throws NonUniqueResultException
     */
    public function getRegistrationEnd() : ?DateTimeImmutable
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

    /**
     * Uloží roli.
     *
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
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Role $role) : void
    {
        $this->_em->remove($role);
        $this->_em->flush();
    }
}
