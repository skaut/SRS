<?php

declare(strict_types=1);

namespace App\Model\Settings;

use Doctrine\ORM\EntityRepository;

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
}
