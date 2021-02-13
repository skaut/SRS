<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Utils\Helpers;
use DateTimeImmutable;
use Nette;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Throwable;

use function filter_var;
use function serialize;
use function unserialize;

use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_INT;

/**
 * Služba pro správu nastavení.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SettingsService implements ISettingsService
{
    /**
     * Nastaví hodnotu položky.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setValue(string $item, ?string $value): void
    {
        $settings = $this->settingsRepository->findByItem($item);
        if ($settings === null) {
            throw new SettingsException('Item ' . $item . ' was not found in table Settings.');
        }

        $settings->setValue($value);
        $this->settingsRepository->save($settings);
    }

    /**
     * Nastaví hodnotu položky typu int.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setIntValue(string $item, ?int $value): void
    {
        if ($value === null) {
            $this->setValue($item, null);
        } else {
            $this->setValue($item, (string) $value);
        }
    }

    /**
     * Nastaví hodnotu položky typu bool.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setBoolValue(string $item, ?bool $value): void
    {
        if ($value === null) {
            $this->setValue($item, null);
        } else {
            $this->setValue($item, (string) $value);
        }
    }


    /**
     * Nastavení hodnoty položky typu datum a čas.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setDateTimeValue(string $item, ?DateTimeImmutable $value): void
    {
        if ($value === null) {
            $this->setValue($item, null);
        } else {
            $this->setValue($item, $value->format(DateTimeImmutable::ISO8601));
        }
    }



    /**
     * Nastavení hodnoty položky typu datum.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setDateValue(string $item, ?DateTimeImmutable $value): void
    {
        if ($value === null) {
            $this->setValue($item, null);
        } else {
            $this->setValue($item, $value->format('Y-m-d'));
        }
    }


    /**
     * Nastavení hodnoty položky typu pole.
     *
     * @param mixed[] $value
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setArrayValue(string $item, array $value): void
    {
        $this->setValue($item, serialize($value));
    }
}
