<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use App\Utils\Helpers;
use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Throwable;

/**
 * Služba pro správu nastavení.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SettingsService
{
    use Nette\SmartObject;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var Cache */
    private $settingsCache;


    public function __construct(SettingsRepository $settingsRepository, IStorage $storage) {
        $this->settingsRepository = $settingsRepository;
        $this->settingsCache      = new Cache($storage, 'Settings');
    }

    /**
     * Vrátí hodnotu položky.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getValue(string $item) : ?string
    {
        $value = $this->settingsCache->load($item);

        if ($value === null) {
            $settings = $this->settingsRepository->findByItem($item);
            if ($settings === null) {
                throw new SettingsException('Item ' . $item . ' was not found in table Settings.');
            }

            $value = $settings->getValue();
            $this->settingsCache->save($item, $value);
        }

        return $value;
    }

    /**
     * Nastaví hodnotu položky.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setValue(string $item, ?string $value) : void
    {
        $settings = $this->settingsRepository->findByItem($item);
        if ($settings === null) {
            throw new SettingsException('Item ' . $item . ' was not found in table Settings.');
        }

        $settings->setValue($value);
        $this->em->flush();

        $this->settingsCache->save($item, $value);
    }

    /**
     * Vrátí hodnotu položky typu int.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getIntValue(string $item) : ?int
    {
        $value = $this->getValue($item);
        if ($value === null) {
            return null;
        }
        return filter_var($value, FILTER_VALIDATE_INT);
    }

    /**
     * Nastaví hodnotu položky typu int.
     *
     * @throws SettingsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function setIntValue(string $item, ?int $value) : void
    {
        if ($value === null) {
            $this->setValue($item, null);
        } else {
            $this->setValue($item, (string) $value);
        }
    }

    /**
     * Vrátí hodnotu položky typu bool.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getBoolValue(string $item) : ?bool
    {
        $value = $this->getValue($item);
        if ($value === null) {
            return null;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Nastaví hodnotu položky typu bool.
     *
     * @throws SettingsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function setBoolValue(string $item, ?bool $value) : void
    {
        if ($value === null) {
            $this->setValue($item, null);
        } else {
            $this->setValue($item, (string) $value);
        }
    }

    /**
     * Vrátí hodnotu položky typu datum a čas.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getDateTimeValue(string $item) : ?DateTime
    {
        $value = $this->getValue($item);
        if ($value === null) {
            return null;
        }
        return new DateTime($value);
    }

    /**
     * Vrátí hodnotu položky typu datum a čas jako text.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getDateTimeValueText(string $item) : ?string
    {
        $value = $this->getValue($item);
        if ($value === null) {
            return null;
        }
        return (new DateTime($value))->format(Helpers::DATETIME_FORMAT);
    }

    /**
     * Nastavení hodnoty položky typu datum a čas.
     *
     * @throws SettingsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function setDateTimeValue(string $item, ?DateTime $value) : void
    {
        if ($value === null) {
            $this->setValue($item, null);
        } else {
            $this->setValue($item, $value->format(DateTime::ISO8601));
        }
    }

    /**
     * Vrátí hodnotu položky typu datum.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getDateValue(string $item) : ?DateTime
    {
        $value = $this->getValue($item);
        if ($value === null) {
            return null;
        }
        return new DateTime($value);
    }

    /**
     * Vrátí hodnotu položky typu datum jako text.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getDateValueText(string $item) : ?string
    {
        $value = $this->getValue($item);
        if ($value === null) {
            return null;
        }
        return (new DateTime($value))->format(Helpers::DATE_FORMAT);
    }

    /**
     * Nastavení hodnoty položky typu datum.
     *
     * @throws SettingsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function setDateValue(string $item, ?DateTime $value) : void
    {
        if ($value === null) {
            $this->setValue($item, null);
        } else {
            $this->setValue($item, $value->format('Y-m-d'));
        }
    }
}
