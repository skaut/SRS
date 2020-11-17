<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use App\Utils\Helpers;
use DateTimeImmutable;
use Nette;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Throwable;
use function filter_var;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_INT;

/**
 * Služba pro správu nastavení.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SettingsService
{
    use Nette\SmartObject;

    private SettingsRepository $settingsRepository;

    private Cache $settingsCache;

    public function __construct(SettingsRepository $settingsRepository, IStorage $storage)
    {
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
        $this->settingsRepository->save($settings);
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
    public function getDateTimeValue(string $item) : ?DateTimeImmutable
    {
        $value = $this->getValue($item);
        if ($value === null) {
            return null;
        }

        return new DateTimeImmutable($value);
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

        return (new DateTimeImmutable($value))->format(Helpers::DATETIME_FORMAT);
    }

    /**
     * Nastavení hodnoty položky typu datum a čas.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setDateTimeValue(string $item, ?DateTimeImmutable $value) : void
    {
        if ($value === null) {
            $this->setValue($item, null);
        } else {
            $this->setValue($item, $value->format(DateTimeImmutable::ISO8601));
        }
    }

    /**
     * Vrátí hodnotu položky typu datum.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getDateValue(string $item) : ?DateTimeImmutable
    {
        $value = $this->getValue($item);
        if ($value === null) {
            return null;
        }

        return new DateTimeImmutable($value);
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

        return (new DateTimeImmutable($value))->format(Helpers::DATE_FORMAT);
    }

    /**
     * Nastavení hodnoty položky typu datum.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setDateValue(string $item, ?DateTimeImmutable $value) : void
    {
        if ($value === null) {
            $this->setValue($item, null);
        } else {
            $this->setValue($item, $value->format('Y-m-d'));
        }
    }

    public function getArrayValue(string $item) : array
    {
        return unserialize($this->getValue($item));
    }

    public function setArrayValue(string $item, array $value) : void
    {
        $this->setValue($item, serialize($value));
    }
}
