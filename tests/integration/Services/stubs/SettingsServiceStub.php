<?php

declare(strict_types=1);

namespace App\Services;

use App\Utils\Helpers;
use DateTimeImmutable;

use function filter_var;
use function serialize;
use function unserialize;

use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_INT;

class SettingsServiceStub implements ISettingsService
{
    /** @var string[] */
    private array $values;

    public function getValue(string $item): ?string
    {
        return $this->values[$item];
    }

    public function setValue(string $item, ?string $value): void
    {
        $this->values[$item] = $value;
    }

    public function getIntValue(string $item): ?int
    {
        $value = $this->getValue($item);
        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_INT);
    }

    public function setIntValue(string $item, ?int $value): void
    {
        if ($value === null) {
            $this->setValue($item, null);
        } else {
            $this->setValue($item, (string) $value);
        }
    }

    public function getBoolValue(string $item): ?bool
    {
        $value = $this->getValue($item);
        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function setBoolValue(string $item, ?bool $value): void
    {
        if ($value === null) {
            $this->setValue($item, null);
        } else {
            $this->setValue($item, (string) $value);
        }
    }

    public function getDateTimeValue(string $item): ?DateTimeImmutable
    {
        $value = $this->getValue($item);
        if ($value === null) {
            return null;
        }

        return new DateTimeImmutable($value);
    }

    public function getDateTimeValueText(string $item): ?string
    {
        $value = $this->getValue($item);
        if ($value === null) {
            return null;
        }

        return (new DateTimeImmutable($value))->format(Helpers::DATETIME_FORMAT);
    }

    public function setDateTimeValue(string $item, ?DateTimeImmutable $value): void
    {
        if ($value === null) {
            $this->setValue($item, null);
        } else {
            $this->setValue($item, $value->format(DateTimeImmutable::ISO8601));
        }
    }

    public function getDateValue(string $item): ?DateTimeImmutable
    {
        $value = $this->getValue($item);
        if ($value === null) {
            return null;
        }

        return new DateTimeImmutable($value);
    }

    public function getDateValueText(string $item): ?string
    {
        $value = $this->getValue($item);
        if ($value === null) {
            return null;
        }

        return (new DateTimeImmutable($value))->format(Helpers::DATE_FORMAT);
    }

    public function setDateValue(string $item, ?DateTimeImmutable $value): void
    {
        if ($value === null) {
            $this->setValue($item, null);
        } else {
            $this->setValue($item, $value->format('Y-m-d'));
        }
    }

    /**
     * @return object[]
     */
    public function getArrayValue(string $item): array
    {
        return unserialize($this->getValue($item));
    }

    /**
     * @param object[] $value
     */
    public function setArrayValue(string $item, array $value): void
    {
        $this->setValue($item, serialize($value));
    }
}
