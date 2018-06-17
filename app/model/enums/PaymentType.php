<?php
declare(strict_types=1);

namespace App\Model\Enums;


class PaymentType
{
    /**
     * Platba v hotovosti.
     */
    const CASH = "cash";

    /**
     * Platba na bankovní účet.
     */
    const BANK = "bank";

    public static $types = [
        self::CASH,
        self::BANK
    ];
}
