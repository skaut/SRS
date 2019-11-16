<?php

declare(strict_types=1);

namespace App\Model\Structure;

use App\Model\EntityRepository;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Translation\Translator;
use function array_map;

/**
 * Třída spravující podakce.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class SubeventRepository extends EntityRepository
{
    /** @var Translator */
    private $translator;


    public function injectTranslator(Translator $translator) : void
    {
        $this->translator = $translator;
    }

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
     * Vrací vytvořené podakce, seřazené podle názvu.
     * @return Collection|Subevent[]
     */
    public function findAllExplicitOrderedByName() : Collection
    {
        $result = $this->createQueryBuilder('s')
            ->where('s.implicit = FALSE')
            ->orderBy('s.name')
            ->getQuery()
            ->getResult();
        return new ArrayCollection($result);
    }

    /**
     * Vrací názvy podakcí, kromě podakce se zadaným id.
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
     * @param int[] $ids
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
     * @param Collection|Subevent[] $subevents
     * @return int[]
     */
    public function findSubeventsIds(Collection $subevents) : array
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $subevents->toArray());
    }

    /**
     * Vrací počet vytvořených podakcí.
     * @throws NonUniqueResultException
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
     * @throws NonUniqueResultException
     */
    public function explicitSubeventsExists() : bool
    {
        return $this->countExplicitSubevents() > 0;
    }

    /**
     * Vrací seznam podakcí jako možnosti pro select.
     * @return string[]
     */
    public function getSubeventsOptions() : array
    {
        $subevents = $this->createQueryBuilder('s')
            ->select('s.id, s.name')
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
     * Vrací seznam podakcí jako možnosti pro select, podakce specifikovaná parametrem je vynechána.
     * @return string[]
     */
    public function getSubeventsWithoutSubeventOptions(int $subeventId) : array
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
     * Vrací seznam podakcí, jako možnosti pro select
     * @return string[]
     */
    public function getExplicitOptions() : array
    {
        $subevents = $this->createQueryBuilder('s')
            ->where('s.implicit = FALSE')
            ->orderBy('s.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($subevents as $subevent) {
            $options[$subevent->getId()] = $subevent->getName();
        }
        return $options;
    }

    /**
     * Vrací seznam podakcí, s informací o obsazenosti, jako možnosti pro select
     * @return string[]
     */
    public function getSubeventsOptionsWithCapacity() : array
    {
        $subevents = $this->createQueryBuilder('s')
            ->orderBy('s.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($subevents as $subevent) {
            if ($subevent->hasLimitedCapacity()) {
                $options[$subevent->getId()] = $this->translator->translate('web.common.subevent_option', null, [
                    'subevent' => $subevent->getName(),
                    'occupied' => $subevent->countUsers(),
                    'total' => $subevent->getCapacity(),
                ]);
            } else {
                $options[$subevent->getId()] = $subevent->getName();
            }
        }
        return $options;
    }

    /**
     * Vrací seznam podakcí, s informací o obsazenosti, jako možnosti pro select
     * @return string[]
     */
    public function getExplicitOptionsWithCapacity() : array
    {
        $subevents = $this->createQueryBuilder('s')
            ->where('s.implicit = FALSE')
            ->orderBy('s.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($subevents as $subevent) {
            if ($subevent->hasLimitedCapacity()) {
                $options[$subevent->getId()] = $this->translator->translate('web.common.subevent_option', null, [
                    'subevent' => $subevent->getName(),
                    'occupied' => $subevent->countUsers(),
                    'total' => $subevent->getCapacity(),
                ]);
            } else {
                $options[$subevent->getId()] = $subevent->getName();
            }
        }
        return $options;
    }

    /**
     * Vrací seznam podakcí, kromě podakcí uživatele, s informací o obsazenosti, jako možnosti pro select.
     * @return string[]
     */
    public function getNonRegisteredSubeventsOptionsWithCapacity(User $user) : array
    {
        $usersSubevents    = $user->getSubevents();
        $usersSubeventsIds = $this->findSubeventsIds($usersSubevents);

        if (empty($usersSubeventsIds)) {
            $subevents = $this->createQueryBuilder('s')
                ->orderBy('s.name')
                ->getQuery()
                ->getResult();
        } else {
            $subevents = $this->createQueryBuilder('s')
                ->where('s.id NOT IN (:subevents)')->setParameter('subevents', $usersSubeventsIds)
                ->orderBy('s.name')
                ->getQuery()
                ->getResult();
        }

        $options = [];
        foreach ($subevents as $subevent) {
            if ($subevent->hasLimitedCapacity()) {
                $options[$subevent->getId()] = $this->translator->translate('web.common.subevent_option', null, [
                    'subevent' => $subevent->getName(),
                    'occupied' => $subevent->countUsers(),
                    'total' => $subevent->getCapacity(),
                ]);
            } else {
                $options[$subevent->getId()] = $subevent->getName();
            }
        }
        return $options;
    }

    /**
     * Vrací seznam podakcí, kromě podakcí uživatele, s informací o obsazenosti, jako možnosti pro select.
     * @return string[]
     */
    public function getNonRegisteredExplicitOptionsWithCapacity(User $user) : array
    {
        $usersSubevents    = $user->getSubevents();
        $usersSubeventsIds = $this->findSubeventsIds($usersSubevents);

        if (empty($usersSubeventsIds)) {
            $subevents = $this->createQueryBuilder('s')
                ->where('s.implicit = FALSE')
                ->orderBy('s.name')
                ->getQuery()
                ->getResult();
        } else {
            $subevents = $this->createQueryBuilder('s')
                ->where('s.implicit = FALSE')
                ->andWhere('s.id NOT IN (:subevents)')->setParameter('subevents', $usersSubeventsIds)
                ->orderBy('s.name')
                ->getQuery()
                ->getResult();
        }

        $options = [];
        foreach ($subevents as $subevent) {
            if ($subevent->hasLimitedCapacity()) {
                $options[$subevent->getId()] = $this->translator->translate('web.common.subevent_option', null, [
                    'subevent' => $subevent->getName(),
                    'occupied' => $subevent->countUsers(),
                    'total' => $subevent->getCapacity(),
                ]);
            } else {
                $options[$subevent->getId()] = $subevent->getName();
            }
        }
        return $options;
    }

    /**
     * Vrací seznam podakcí, s informací o počtu uživatelů, jako možnosti pro select.
     * @return string[]
     */
    public function getSubeventsOptionsWithUsersCount() : array
    {
        $subevents = $this->createQueryBuilder('s')
            ->orderBy('s.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($subevents as $subevent) {
            $options[$subevent->getId()] = $this->translator->translate(
                'admin.common.subevent_option',
                $subevent->countUsers(),
                [
                    'subevent' => $subevent->getName(),
                ]
            );
        }
        return $options;
    }

    /**
     * Uloží podakci.
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
