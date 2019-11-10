<?php

declare(strict_types=1);

namespace App\Model\Enums;

class ProgramMandatoryType
{
    /**
     * Volitelný blok.
     * @var string
     */
    public const VOLUNTARY = 'voluntary';

    /**
     * Povinný blok.
     * @var string
     */
    public const MANDATORY = 'mandatory';

    /**
     * Automaticky registrovaný povinný blok.
     * @var string
     */
    public const AUTO_REGISTERED = 'auto_registered';

    /** @var string[] */
    public static $types = [
        self::VOLUNTARY,
        self::MANDATORY,
        self::AUTO_REGISTERED,
    ];
}
