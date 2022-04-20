<?php

declare(strict_types=1);

namespace App\Model\Enums;

class ProgramMandatoryType
{
    /**
     * Volitelný blok
     */
    public const VOLUNTARY = 'voluntary';

    /**
     * Povinný blok
     */
    public const MANDATORY = 'mandatory';

    /**
     * Automaticky registrovaný povinný blok
     */
    public const AUTO_REGISTERED = 'auto_registered';

    /** @var string[] */
    public static array $types = [
        self::VOLUNTARY,
        self::MANDATORY,
        self::AUTO_REGISTERED,
    ];
}
