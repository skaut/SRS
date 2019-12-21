<?php

declare(strict_types=1);

namespace App\Model\ACL;

use App\Model\User\User;
use DateTime;
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
     * Vrací roli podle názvu.
     */
    public function findByName(string $name) : ?Role
    {
        return $this->findOneBy(['name' => $name]);
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
     * Vrací registrovatelné role.
     *
     * @return Role[]
     */
    public function findAllRegisterable() : array
    {
        return $this->findBy(['registerable' => true]);
    }

    /**
     * Vrací role s omezenou kapacitou.
     *
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
     *
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
     *
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
     * Vraci role, ktere jsou tuto chvíli registrovatelné, seřazené podle názvu.
     *
     * @return Collection|Role[]
     */
    public function findAllRegisterableNowOrderedByName() : Collection
    {
        $result = $this->createQueryBuilder('r')
            ->select('r')
            ->where($this->createQueryBuilder('r')->expr()->andX(
                $this->createQueryBuilder('r')->expr()->eq('r.registerable', true),
                $this->createQueryBuilder('r')->expr()->orX(
                    $this->createQueryBuilder('r')->expr()->lte('r.registerableFrom', 'CURRENT_TIMESTAMP()'),
                    $this->createQueryBuilder('r')->expr()->isNull('r.registerableFrom')
                ),
                $this->createQueryBuilder('r')->expr()->orX(
                    $this->createQueryBuilder('r')->expr()->gte('r.registerableTo', 'CURRENT_TIMESTAMP()'),
                    $this->createQueryBuilder('r')->expr()->isNull('r.registerableTo')
                )
            ))
            ->orderBy('r.name')
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Vrací role, které jsou v tuto chvíli registrovatelné nebo je uživatel má, seřazené podle názvu.
     *
     * @return Role[]
     */
    public function findAllRegisterableNowOrUsersOrderedByName(User $user) : array
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->leftJoin('r.users', 'u')
            ->where($this->createQueryBuilder('r')->expr()->orX(
                $this->createQueryBuilder('r')->expr()->andX(
                    $this->createQueryBuilder('r')->expr()->eq('r.registerable', true),
                    $this->createQueryBuilder('r')->expr()->orX(
                        $this->createQueryBuilder('r')->expr()->lte('r.registerableFrom', 'CURRENT_TIMESTAMP()'),
                        $this->createQueryBuilder('r')->expr()->isNull('r.registerableFrom')
                    ),
                    $this->createQueryBuilder('r')->expr()->orX(
                        $this->createQueryBuilder('r')->expr()->gte('r.registerableTo', 'CURRENT_TIMESTAMP()'),
                        $this->createQueryBuilder('r')->expr()->isNull('r.registerableTo')
                    )
                ),
                $this->createQueryBuilder('r')->expr()->eq('u.id', $user->getId())
            ))
            ->orderBy('r.name')
            ->getQuery()
            ->getResult();
    }

    public function incrementOccupancy(Role $role) : void
    {
        $this->_em->createQuery('UPDATE App\Model\ACL\Role r SET r.occupancy = r.occupancy + 1 WHERE r.id = :rid')
            ->setParameter('rid', $role->getId())
            ->getResult();
    }

    public function decrementOccupancy(Role $role) : void
    {
        $this->_em->createQuery('UPDATE App\Model\ACL\Role r SET r.occupancy = r.occupancy - 1 WHERE r.id = :rid')
            ->setParameter('rid', $role->getId())
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getRegistrationStart() : ?DateTime
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
    public function getRegistrationEnd() : ?DateTime
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
