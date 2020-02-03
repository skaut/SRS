<?php

declare(strict_types=1);

namespace App\Model\Enums;

class MaturityType
{
    /**
     * Neomezená splatnost.
     */
    public const UNLIMITED = 'unlimited';

    /**
     * Pevné datum splatnosti.
     */
    public const DATE = 'date';

    /**
     * Splatnost vypočtená podle počtu dní od odeslání přihlášky.
     */
    public const DAYS = 'days';

    /**
     * Splatnost vypočtená podle počtu pracovních dní od odeslání přihlášky.
     */
    public const WORK_DAYS = 'work_days';

    /** @var string[] */
    public static $types = [
        self::UNLIMITED,
        self::DATE,
        self::DAYS,
        self::WORK_DAYS,
    ];
}
