<?php

namespace App\Model\Structure;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující podakce.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SubeventRepository extends EntityRepository
{
    /**
     * Vrací podakci podle id.
     * @param $id
     * @return Subevent|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací názvy podakcí, kromě podakce se zadaným id.
     * @param $id
     * @return array
     */
    public function findOthersNames($id)
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
     * @param $ids
     * @return Collection
     */
    public function findSubeventsByIds($ids)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    /**
     * Vrací id podakcí.
     * @param $subevents
     * @return array
     */
    public function findSubeventsIds($subevents)
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $subevents->toArray());
    }

    /**
     * Vrací počet schválených uživatelů v podakci.
     * @param Subevent $subevent
     * @return int
     */
    public function countApprovedUsersInSubevent(Subevent $subevent)
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(u.id)')
            ->leftJoin('s.users', 'u', 'WITH', 'u.approved = true')
            ->where('s.id = :id')->setParameter('id', $subevent->getId())
            ->getQuery()
            ->getSingleScalarResult(); //TODO
    }

    /**
     * Vrací seznam podakcí jako možnosti pro select, podakce specifikovaná parametrem je vynechána.
     * @param $subeventId
     * @return array
     */
    public function getSubeventsWithoutSubeventOptions($subeventId)
    {
        $subevents = $this->createQueryBuilder('s')
            ->select('s.id, s.name')
            ->where('s.id != :id')->setParameter('id', $subeventId)
            ->orderBy('s.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($subevents as $subevent) {
            $options[$subevent['id']] = $subevent['name'];
        }
        return $options;
    }

    /**
     * Uloží podakci.
     * @param Subevent $subevent
     */
    public function save(Subevent $subevent)
    {
        $this->_em->persist($subevent);
        $this->_em->flush();
    }

    /**
     * Odstraní podakci.
     * @param Subevent $subevent
     */
    public function remove(Subevent $subevent)
    {
        $this->_em->remove($subevent);
        $this->_em->flush();
    }
}
