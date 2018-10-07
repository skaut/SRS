<?php

declare(strict_types=1);

namespace App\Model\Enums;

class PaymentState
{
    /**
     * Spárováno automaticky.
     * @var string
     */
    public const PAIRED_AUTO = 'paired_auto';

    /**
     * Spárováno ručně.
     * @var string
     */
    public const PAIRED_MANUAL = 'paired_manual';

    /**
     * Nespárováno - nesouhlasí poplatek.
     * @var string
     */
    public const NOT_PAIRED_FEE = 'not_paired_fee';

    /**
     * Nespárováno - neexistující variabilní symbol.
     * @var string
     */
    public const NOT_PAIRED_VS = 'not_paired_vs';

    /**
     * Nespárováno - spárovaná přihláška zrušena.
     * @var string
     */
    public const NOT_PAIRED_CANCELED = 'not_paired_canceled';

    /**
     * Nespárováno - nevybrána spárovaná přihláška.
     * @var string
     */
    public const NOT_PAIRED = 'not_paired';

    /** @var string[] */
    public static $types = [
        self::PAIRED_AUTO,
        self::PAIRED_MANUAL,
        self::NOT_PAIRED_FEE,
        self::NOT_PAIRED_VS,
        self::NOT_PAIRED_CANCELED,
        self::NOT_PAIRED,
    ];
}
