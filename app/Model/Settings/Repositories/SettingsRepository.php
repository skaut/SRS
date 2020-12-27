<?php

declare(strict_types=1);

namespace App\Model\Settings\Repositories;

use App\Model\Settings\Settings;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující nastavení.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SettingsRepository extends EntityRepository
{
    /**
     * Vrací položku nastavení podle názvu.
     */
    public function findByItem(?string $item) : ?Settings
    {
        return $this->findOneBy(['item' => $item]);
    }

    /**
     * @throws ORMException
     */
    public function save(Settings $settings) : void
    {
        $this->_em->persist($settings);
        $this->_em->flush();
    }
}
