<?php

declare(strict_types=1);

namespace App\Model\Enums;

class PaymentType
{
    /**
     * Platba v hotovosti.
     */
    public const CASH = 'cash';

    /**
     * Platba na bankovní účet.
     */
    public const BANK = 'bank';

    /**
     * Uživatel zaplatil část přihlášek hotově a část na bankovní účet.
     */
    public const MIXED = 'mixed';

    /** @var string[] */
    public static $types = [
        self::CASH,
        self::BANK,
    ];
}
