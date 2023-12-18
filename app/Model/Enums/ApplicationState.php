<?php

declare(strict_types=1);

namespace App\Model\Enums;

class ApplicationState
{
    /**
     * Čeká na platbu.
     */
    public const WAITING_FOR_PAYMENT = 'waiting_for_payment';

    /**
     * Automaticky zrušeno kvůli nezaplacení.
     */
    public const CANCELED_NOT_PAID = 'canceled_not_paid';

    /**
     * Zrušeno.
     */
    public const CANCELED = 'canceled';

    /**
     * Zaplaceno.
     */
    public const PAID = 'paid';

    /**
     * Zaplaceno (zdarma).
     */
    public const PAID_FREE = 'paid_free';

    /**
     * Ceka na naplneni.
     */
    public const WAITING_FOR_FILLING = 'waiting_for_filling';
}
