<?php

declare(strict_types=1);

namespace App\Model\Settings\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Settings;
use Doctrine\ORM\EntityManagerInterface;

use function assert;

/**
 * Třída spravující nastavení.
 */
class SettingsRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Settings::class);
    }

    /**
     * Vrací položku nastavení podle názvu.
     *
     * @throws SettingsItemNotFoundException
     */
    public function findByItem(string $item): Settings
    {
        $setting = $this->getRepository()->findOneBy(['item' => $item]);

        if ($setting === null) {
            throw new SettingsItemNotFoundException('Item ' . $item . ' was not found in table Settings.');
        }

        assert($setting instanceof Settings);

        return $setting;
    }

    public function save(Settings $settings): void
    {
        $this->em->persist($settings);
        $this->em->flush();
    }
}
