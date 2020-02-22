<?php

declare(strict_types=1);

namespace App\Model\Structure;

use App\Model\User\User;
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
 * Třída spravující podakce.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class SubeventRepository extends EntityRepository
{
    /**
     * Vrací podakci podle id.
     */
    public function findById(?int $id) : ?Subevent
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací implicitní podakci.
     */
    public function findImplicit() : Subevent
    {
        return $this->findOneBy(['implicit' => true]);
    }

    /**
     * Vrací názvy všech podakcí.
     *
     * @return string[]
     */
    public function findAllNames() : array
    {
        $names = $this->createQueryBuilder('s')
            ->select('s.name')
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $names);
    }

    /**
     * Vrací podakce splňující podmínku seřazené podle názvu.
     *
     * @return Collection|Subevent[]
     */
    public function findFilteredSubevents(bool $explicitOnly, bool $registerableNowOnly, bool $notRegisteredOnly, bool $includeUsers, ?User $user = null) : Collection
    {
        $qb = $this->createQueryBuilder('s');

        $query = $qb
            ->select('s')
            ->where('1 = 1');

        if ($explicitOnly) {
            $query = $query->andWhere($qb->expr()->eq('s.implicit', 'false'));
        }

        if ($registerableNowOnly) {
            $query = $query
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->lte('s.registerableFrom', 'CURRENT_TIMESTAMP()'),
                    $qb->expr()->isNull('s.registerableFrom')
                ))
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->gte('s.registerableTo', 'CURRENT_TIMESTAMP()'),
                    $qb->expr()->isNull('s.registerableTo')
                ));
        }

        if ($notRegisteredOnly) {
            $query = $query->andWhere('s not in (:users_subevents)')->setParameter('users_subevents', $user->getSubevents());
        }

        if ($includeUsers) {
            $query = $query->orWhere('s in (:users_subevents)')->setParameter('users_subevents', $user->getSubevents());
        }

        $result = $query
            ->orderBy('s.name')
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Vrací názvy podakcí, kromě podakce se zadaným id.
     *
     * @return string[]
     */
    public function findOthersNames(int $id) : array
    {
        $names = $this->createQueryBuilder('s')
            ->select('s.name')
            ->where('s.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $names);
    }

    /**
     * Vrací podakce podle id.
     *
     * @param int[] $ids
     *
     * @return Collection|Subevent[]
     */
    public function findSubeventsByIds(array $ids) : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);

        return $this->matching($criteria);
    }

    /**
     * Vrací id podakcí.
     *
     * @param Collection|Subevent[] $subevents
     *
     * @return int[]
     */
    public function findSubeventsIds(Collection $subevents) : array
    {
        return array_map(static function (Subevent $o) {
            return $o->getId();
        }, $subevents->toArray());
    }

    /**
     * Vrací počet vytvořených podakcí.
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countExplicitSubevents() : int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.implicit = FALSE')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací, zda jsou vytvořeny podakce.
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function explicitSubeventsExists() : bool
    {
        return $this->countExplicitSubevents() > 0;
    }

    /**
     * Uloží podakci.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Subevent $subevent) : void
    {
        $this->_em->persist($subevent);
        $this->_em->flush();
    }

    /**
     * Odstraní podakci.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Subevent $subevent) : void
    {
        $this->_em->remove($subevent);
        $this->_em->flush();
    }

    public function incrementOccupancy(Subevent $subevent) : void
    {
        $this->_em->createQuery('UPDATE App\Model\Structure\Subevent s SET s.occupancy = s.occupancy + 1 WHERE s.id = :sid')
            ->setParameter('sid', $subevent->getId())
            ->getResult();
    }

    public function decrementOccupancy(Subevent $subevent) : void
    {
        $this->_em->createQuery('UPDATE App\Model\Structure\Subevent s SET s.occupancy = s.occupancy - 1 WHERE s.id = :sid')
            ->setParameter('sid', $subevent->getId())
            ->getResult();
    }
}
