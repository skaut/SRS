<?php

declare(strict_types=1);

namespace App\Model\Enums;

class ProgramRegistrationType
{
    /**
     * Zapisování programů povoleno
     */
    public const ALLOWED = 'allowed';

    /**
     * Zapisování programů nepovoleno
     */
    public const NOT_ALLOWED = 'not_allowed';

    /**
     * Zapisování programů povoleno od do
     */
    public const ALLOWED_FROM_TO = 'allowed_from_to';

    /** @var string[] */
    public static array $types = [
        self::ALLOWED,
        self::NOT_ALLOWED,
        self::ALLOWED_FROM_TO,
    ];
}
