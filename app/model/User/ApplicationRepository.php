<?php

namespace App\Model\User;

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
     * Uloží přihlášku.
     * @param Application $application
     */
    public function save(Application $application)
    {
        $this->_em->persist($application);
        $this->_em->flush();
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
}
