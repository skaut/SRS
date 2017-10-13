<?php

namespace App\Model\User;

use App\Model\Enums\ApplicationState;
use App\Model\Settings\SettingsRepository;
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
     * @param $id
     * @return Application|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací pořadí poslední odeslané přihlášky.
     * @return int
     */
    public function findLastApplicationOrder()
    {
        return $this->createQueryBuilder('a')
            ->select('MAX(a.applicationOrder)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací přihlášky čekající na platbu.
     * @return Application[]
     */
    public function findWaitingForPaymentApplications()
    {
        return $this->createQueryBuilder('a')
            ->where('a.state = :state')->setParameter('state', ApplicationState::WAITING_FOR_PAYMENT)
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací true, pokud existuje přihláška s tímto variabilním symbolem.
     * @param $variableSymbol
     * @return bool
     */
    public function variableSymbolExists($variableSymbol)
    {
        $res = $this->createQueryBuilder('a')
            ->where('a.variableSymbol = :vs')->setParameter('vs', $variableSymbol)
            ->getQuery()
            ->getResult();
        return !empty($res);
    }

    /**
     * Uloží přihlášku.
     * @param Application $application
     */
    public function save(Application $application)
    {
        $this->_em->persist($application);
        $this->_em->flush();
    }

    /**
     * Odstraní přihlášku.
     * @param Application $application
     */
    public function remove(Application $application)
    {
        $this->_em->remove($application);
        $this->_em->flush();
    }
}
