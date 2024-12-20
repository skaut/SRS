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
}
