<?php

declare(strict_types=1);

namespace App\Model\User;

use App\Model\Enums\ApplicationState;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\EntityRepository;

/**
 * Třída spravující přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationRepository extends EntityRepository
{
    /**
     * Vrací přihlášku podle id.
     */
    public function findById(?int $id) : ?Application
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací přihlášky podle id, které mají společné všechny verze přihlášky.
     * @return Collection|Application[]
     */
    public function findByApplicationId(int $id) : Collection
    {
        $result = $this->findBy(['applicationId' => $id]);
        return new ArrayCollection($result);
    }

    /**
     * @return Collection|Application[]
     */
    public function findValid() : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('validTo'));
        return $this->matching($criteria);
    }

    public function findValidByVariableSymbol(string $variableSymbol) : ?Application
    {
        return $this->createQueryBuilder('a')
            ->select('a')
            ->join('a.variableSymbol', 'variableSymbol')
            ->where('variableSymbol = :variableSymbol')->setParameter('variableSymbol', $variableSymbol)
            ->andWhere('a.validTo IS NULL')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Uloží přihlášku.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Application $application) : void
    {
        $this->_em->persist($application);
        $this->_em->flush();
    }

    /**
     * Odstraní přihlášku.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Application $application) : void
    {
        $this->_em->remove($application);
        $this->_em->flush();
    }

    /**
     * Vrací přihlášky podle id.
     * @param int[] $ids
     * @return Collection|Application[]
     */
    public function findApplicationsByIds(array $ids) : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids));
        return $this->matching($criteria);
    }

    /**
     * Vrací id přihlášek.
     * @param Collection|Application[] $applications
     * @return int[]
     */
    public function findApplicationsIds(Collection $applications) : array
    {
        return array_map(function (Application $o) {
            return $o->getId();
        }, $applications->toArray());
    }

    /**
     * @param Collection|Application[] $pairedApplications
     * @return Collection|Application[]
     */
    public function findWaitingForPaymentOrPairedApplications(Collection $pairedApplications) : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('validTo'))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->eq('state', ApplicationState::WAITING_FOR_PAYMENT),
                Criteria::expr()->in('id', $pairedApplications->map(function (Application $application) {
                    return $application->getId();
                })
                    ->toArray()
                )));

        return $this->matching($criteria);
    }

    /**
     * @return string[]
     */
    public function getApplicationsVariableSymbolsOptions() : array
    {
        $options = [];
        foreach ($this->findValid() as $application) {
            $options[$application->getId()] = $application->getUser()->getLastName() . ' ' . $application->getUser()->getFirstName() . ' (' . $application->getVariableSymbolText() . ')';
        }
        return $options;
    }

    /**
     * @param Collection|Application[] $pairedApplications
     * @return string[]
     */
    public function getWaitingForPaymentOrPairedApplicationsVariableSymbolsOptions(Collection $pairedApplications) : array
    {
        $options = [];
        foreach ($this->findWaitingForPaymentOrPairedApplications($pairedApplications) as $application) {
            $options[$application->getId()] = $application->getUser()->getLastName() . ' ' . $application->getUser()->getFirstName() . ' (' . $application->getVariableSymbolText() . ')';
        }
        return $options;
    }
}
