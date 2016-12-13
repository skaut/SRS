<?php

namespace App\Model;

class Payment
{
    const CASH = "cash";
    const BANK = "bank";

    public static $types = [
        CASH,
        BANK
    ];
}