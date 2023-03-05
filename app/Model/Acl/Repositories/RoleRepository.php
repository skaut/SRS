<?php

declare(strict_types=1);

namespace App\Model\Acl\Repositories;

use App\Model\Acl\Role;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

use function array_map;

/**
 * Třída spravující role.
 */
class RoleRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Role::class);
    }

    /**
     * @return Collection<int, Role>
     */
    public function findAll(): Collection
    {
        $result = $this->getRepository()->findAll();

        return new ArrayCollection($result);
    }

    /**
     * Vrací roli podle id.
     */
    public function findById(?int $id): ?Role
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací systémovou roli podle systémového názvu.
     */
    public function findBySystemName(string $name): Role
    {
        return $this->getRepository()->findOneBy(['systemName' => $name]);
    }

    /**
     * Vrací role podle typu.
     *
     * @return Role[]
     */
    public function findByType(string $type): array
    {
        return $this->getRepository()->findBy(['type' => $type]);
    }

    /**
     * Vrací id naposledy přidané role.
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findLastId(): ?int
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
    public function findOthersNames(int $id): array
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
     * @return Collection<int, Role>
     */
    public function findRolesByIds(array $ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);

        return $this->getRepository()->matching($criteria);
    }

    /**
     * Vrací role s počty uživatelů.
     *
     * @param int[] $rolesIds
     *
     * @return string[][]
     */
    public function countUsersInRoles(array $rolesIds): array
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
     * @param Collection<int, Role> $roles
     *
     * @return int[]
     */
    public function findRolesIds(Collection $roles): array
    {
        return array_map(static fn (Role $o) => $o->getId(), $roles->toArray());
    }

    /**
     * Vrací role splňující podmínku seřazené podle názvu.
     *
     * @return Collection<int, Role>
     */
    public function findFilteredRoles(bool $registerableNowOnly, bool $subeventsRoleOnly, bool $includeUsers, ?User $user = null): Collection
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

        if ($includeUsers) {
            $query = $query->orWhere('r in (:users_roles)')->setParameter('users_roles', $user->getRoles());
        }

        $result = $query
            ->orderBy('r.name')
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    public function incrementOccupancy(Role $role): void
    {
        $this->em->createQuery('UPDATE App\Model\Acl\Role r SET r.occupancy = r.occupancy + 1 WHERE r.id = :rid')
            ->setParameter('rid', $role->getId())
            ->getResult();
    }

    public function decrementOccupancy(Role $role): void
    {
        $this->em->createQuery('UPDATE App\Model\Acl\Role r SET r.occupancy = r.occupancy - 1 WHERE r.id = :rid')
            ->setParameter('rid', $role->getId())
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getRegistrationStart(): ?DateTimeImmutable
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
    public function getRegistrationEnd(): ?DateTimeImmutable
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
     */
    public function save(Role $role): void
    {
        $this->em->persist($role);
        $this->em->flush();
    }

    /**
     * Odstraní roli.
     */
    public function remove(Role $role): void
    {
        $this->em->remove($role);
        $this->em->flush();
    }
}
