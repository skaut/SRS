<?php

namespace App\Model\Enums;


class VariableSymbolType
{
    /**
     * Generování podle datumu narození.
     */
    const BIRTH_DATE = "birth_date";

    /**
     * Generování podle pořadí odeslání přihlášky.
     */
    const ORDER = "order";

    public static $types = [
        self::BIRTH_DATE,
        self::ORDER
    ];
}
