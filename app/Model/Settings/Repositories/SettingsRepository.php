<?php

declare(strict_types=1);

namespace App\Model\Settings\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující nastavení.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SettingsRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Settings::class);
    }

    /**
     * Vrací položku nastavení podle názvu.
     */
    public function findByItem(string $item): Settings
    {
        $setting = $this->getRepository()->findOneBy(['item' => $item]);

        if ($setting === null) {
            throw new SettingsException('Item ' . $item . ' was not found in table Settings.');
        }

        assert($setting instanceof Settings);

        return $setting;
    }

    /**
     * @throws ORMException
     */
    public function save(Settings $settings): void
    {
        $this->em->persist($settings);
        $this->em->flush();
    }
}
