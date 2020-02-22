<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Structure\SubeventRepository;
use App\Model\User\User;
use Nette;
use Nette\Localization\ITranslator;

/**
 * Služba pro správu podakcí.
 *
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SubeventService
{
    use Nette\SmartObject;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var ITranslator */
    private $translator;

    public function __construct(SubeventRepository $subeventRepository, ITranslator $translator)
    {
        $this->subeventRepository = $subeventRepository;
        $this->translator         = $translator;
    }

    /**
     * Vrací seznam podakcí jako možnosti pro select.
     *
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
     *
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
     * Vrací seznam podakcí splňujících podmínku, s informací o obsazenosti, jako možnosti pro select.
     *
     * @return string[]
     */
    public function getSubeventsOptionsWithCapacity(bool $explicitOnly, bool $registerableNowOnly, bool $notRegisteredOnly, bool $includeUsers, ?User $user = null) : array
    {
        $subevents = $this->subeventRepository->findFilteredSubevents($explicitOnly, $registerableNowOnly, $notRegisteredOnly, $includeUsers, $user);

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
     *
     * @return string[]
     */
    public function getSubeventsOptionsWithUsersCount() : array
    {
        $subevents = $this->subeventRepository->findFilteredSubevents(false, false, false, false);

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
