<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Settings\Exceptions\SettingsException;
use DateTimeImmutable;
use Throwable;

/**
 * Služba pro správu nastavení.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface ISettingsService
{
    /**
     * Vrátí hodnotu položky.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getValue(string $item): ?string;

    /**
     * Nastaví hodnotu položky.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setValue(string $item, ?string $value): void;

    /**
     * Vrátí hodnotu položky typu int.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getIntValue(string $item): ?int;

    /**
     * Nastaví hodnotu položky typu int.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setIntValue(string $item, ?int $value): void;

    /**
     * Vrátí hodnotu položky typu bool.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getBoolValue(string $item): ?bool;

    /**
     * Nastaví hodnotu položky typu bool.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setBoolValue(string $item, ?bool $value): void;

    /**
     * Vrátí hodnotu položky typu datum a čas.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getDateTimeValue(string $item): ?DateTimeImmutable;

    /**
     * Vrátí hodnotu položky typu datum a čas jako text.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getDateTimeValueText(string $item): ?string;

    /**
     * Nastavení hodnoty položky typu datum a čas.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setDateTimeValue(string $item, ?DateTimeImmutable $value): void;

    /**
     * Vrátí hodnotu položky typu datum.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getDateValue(string $item): ?DateTimeImmutable;

    /**
     * Vrátí hodnotu položky typu datum jako text.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getDateValueText(string $item): ?string;

    /**
     * Nastavení hodnoty položky typu datum.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setDateValue(string $item, ?DateTimeImmutable $value): void;

    /**
     * Vrátí hodnotu položky typu pole.
     *
     * @return mixed[]
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getArrayValue(string $item): array;

    /**
     * Nastavení hodnoty položky typu pole.
     *
     * @param mixed[] $value
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function setArrayValue(string $item, array $value): void;
}
