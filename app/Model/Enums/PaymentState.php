<?php

declare(strict_types=1);

namespace App\Model\Enums;

class PaymentState
{
    /**
     * Spárováno automaticky.
     */
    public const PAIRED_AUTO = 'paired_auto';

    /**
     * Spárováno ručně.
     */
    public const PAIRED_MANUAL = 'paired_manual';

    /**
     * Spárovaná přihláška byla zrušena.
     */
    public const PAIRED_CANCELED = 'paired_canceled';

    /**
     * Nespárováno - nesouhlasí poplatek.
     */
    public const NOT_PAIRED_FEE = 'not_paired_fee';

    /**
     * Nespárováno - neexistující variabilní symbol.
     */
    public const NOT_PAIRED_VS = 'not_paired_vs';

    /**
     * Nespárováno - spárovaná přihláška zrušena.
     */
    public const NOT_PAIRED_CANCELED = 'not_paired_canceled';

    /**
     * Nespárováni - přihláška již byla zaplacena.
     */
    public const NOT_PAIRED_PAID = 'not_paired_paid';

    /**
     * Nespárováno - nevybrána spárovaná přihláška.
     */
    public const NOT_PAIRED = 'not_paired';

    /** @var string[] */
    public static array $states = [
        self::PAIRED_AUTO,
        self::PAIRED_MANUAL,
        self::PAIRED_CANCELED,
        self::NOT_PAIRED_FEE,
        self::NOT_PAIRED_VS,
        self::NOT_PAIRED_CANCELED,
        self::NOT_PAIRED_PAID,
        self::NOT_PAIRED,
    ];
}
