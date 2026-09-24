<?php

declare(strict_types=1);

namespace App\Model\Enums;

class ApplicationState
{
    /**
     * Čeká na platbu
     */
    public const WAITING_FOR_PAYMENT = 'waiting_for_payment';

    /**
     * Zrušeno
     */
    public const CANCELED = 'canceled';

    /**
     * Zrušeno (automaticky kvůli nezaplacení)
     */
    public const CANCELED_NOT_PAID = 'canceled_not_paid';

    /**
     * Zrušeno (převod na jiného účastníka)
     */
    public const CANCELED_TRANSFERED = 'canceled_transfered';

    /**
     * Zaplaceno
     */
    public const PAID = 'paid';

    /**
     * Zaplaceno (zdarma)
     */
    public const PAID_FREE = 'paid_free';

    /**
     * Zaplaceno (převedeno od jiného účastníka)
     */
    public const PAID_TRANSFERED = 'paid_transfered';

    /**
     * Stavy zaplacené přihlášky.
     */
    public const PAID_STATES = [
        self::PAID,
        self::PAID_FREE,
        self::PAID_TRANSFERED,
    ];

    /**
     * Stavy nezrušené přihlášky, tedy doplněk ke CANCELED, CANCELED_NOT_PAID
     * a CANCELED_TRANSFERED.
     */
    public const NOT_CANCELED_STATES = [
        self::WAITING_FOR_PAYMENT,
        self::PAID,
        self::PAID_FREE,
        self::PAID_TRANSFERED,
    ];
}
