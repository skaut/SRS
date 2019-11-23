<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\ACL\PermissionRepository;
use App\Model\ACL\ResourceRepository;
use App\Model\ACL\RoleRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Translation\Translator;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use function array_map;
use Nette;

/**
 *
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class SubeventService
{
    use Nette\SmartObject;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var Translator */
    private $translator;


    public function __construct(SubeventRepository $subeventRepository, Translator $translator)
    {
        $this->subeventRepository = $subeventRepository;
        $this->translator     = $translator;
    }

    /**
     * Vrací seznam podakcí jako možnosti pro select.
     * @return string[]
     */
    public function getSubeventsOptions() : array
    {
        $subevents = $this->subeventRepository->createQueryBuilder('s')
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
        $subevents = $this->subeventRepository->createQueryBuilder('s')
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
        $subevents = $this->subeventRepository->createQueryBuilder('s')
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
        $subevents = $this->subeventRepository->createQueryBuilder('s')
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
        $subevents = $this->subeventRepository->createQueryBuilder('s')
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
        $usersSubeventsIds = $this->subeventRepository->findSubeventsIds($usersSubevents);

        if (empty($usersSubeventsIds)) {
            $subevents = $this->subeventRepository->createQueryBuilder('s')
                ->orderBy('s.name')
                ->getQuery()
                ->getResult();
        } else {
            $subevents = $this->subeventRepository->createQueryBuilder('s')
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
        $usersSubeventsIds = $this->subeventRepository->findSubeventsIds($usersSubevents);

        if (empty($usersSubeventsIds)) {
            $subevents = $this->subeventRepository->createQueryBuilder('s')
                ->where('s.implicit = FALSE')
                ->orderBy('s.name')
                ->getQuery()
                ->getResult();
        } else {
            $subevents = $this->subeventRepository->createQueryBuilder('s')
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
        $subevents = $this->subeventRepository->createQueryBuilder('s')
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
}
