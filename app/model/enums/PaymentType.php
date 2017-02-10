<?php

namespace App\Model\Enums;


class PaymentType
{
    const CASH = "cash";
    const BANK = "bank";

    public static $types = [
        self::CASH,
        self::BANK
    ];
}