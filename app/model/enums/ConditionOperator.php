<?php

declare(strict_types=1);

namespace App\Model\Enums;

class ConditionOperator
{
    /**
     * A.
     *
     * @var string
     */
    public const OPERATOR_AND = 'and';

    /**
     * Nebo.
     *
     * @var string
     */
    public const OPERATOR_OR = 'or';

    /** @var string[] */
    public static $operators = [
        self::OPERATOR_AND,
        self::OPERATOR_OR,
    ];
}
