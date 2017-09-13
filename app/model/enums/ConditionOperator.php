<?php

namespace App\Model\Enums;


class ConditionOperator
{
    /**
     * A.
     */
    const OPERATOR_AND = "and";

    /**
     * Nebo.
     */
    const OPERATOR_OR = "or";

    public static $operators = [
        self::OPERATOR_AND,
        self::OPERATOR_OR
    ];
}
