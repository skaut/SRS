<?php

namespace App\Model\Enums;


class RegisterProgramsType
{
    /**
     * Zapisování programů povoleno.
     */
    const ALLOWED = "allowed";

    /**
     * Zapisování programů nepovoleno.
     */
    const NOT_ALLOWED = "not_allowed";

    /**
     * Zapisování programů povoleno od do.
     */
    const ALLOWED_FROM_TO = "allowed_from_to";

    public static $types = [
        self::ALLOWED,
        self::NOT_ALLOWED,
        self::ALLOWED_FROM_TO
    ];
}
