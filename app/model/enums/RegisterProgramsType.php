<?php

declare(strict_types=1);

namespace App\Model\Enums;

class RegisterProgramsType
{
    /**
     * Zapisování programů povoleno.
     * @var string
     */
    public const ALLOWED = 'allowed';

    /**
     * Zapisování programů nepovoleno.
     * @var string
     */
    public const NOT_ALLOWED = 'not_allowed';

    /**
     * Zapisování programů povoleno od do.
     * @var string
     */
    public const ALLOWED_FROM_TO = 'allowed_from_to';

    /** @var string[] */
    public static $types = [
        self::ALLOWED,
        self::NOT_ALLOWED,
        self::ALLOWED_FROM_TO,
    ];
}
