<?php

declare(strict_types=1);

namespace App\Model\User;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr;
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
     * @return Collection|Application[]
     */
    public function findValidApplicationsWithFee() : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('validTo'))
            ->andWhere(Criteria::expr()->gt('fee', 0));

        return $this->matching($criteria);
    }

    public function getApplicationsVariableSymbolsOptions() : array
    {
        $options = [];
        foreach ($this->findValidApplicationsWithFee() as $application) {
            $options[$application->getId()] = $application->getUser()->getLastName() . ' ' . $application->getUser()->getFirstName() . ' (' . $application->getVariableSymbolText() . ')';
        }
        return $options;
    }
}
