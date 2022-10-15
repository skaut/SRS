<?php

declare(strict_types=1);

namespace App\Model\Enums;

class RoleType
{
    /**
     * Individuální role.
     */
    public const INDIVIDUAL = 'individual';

    /**
     * Družinová role.
     */
    public const PATROL = 'patrol';

    /**
     * Oddílová role.
     */
    public const TROOP = 'troop';

    /** @var string[] */
    public static array $types = [
        self::INDIVIDUAL,
        self::PATROL,
        self::TROOP,
    ];
}
