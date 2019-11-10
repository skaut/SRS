<?php

declare(strict_types=1);

namespace App\Model\Enums;

class ApplicationState
{
    /**
     * Čeká na platbu.
     * @var string
     */
    public const WAITING_FOR_PAYMENT = 'waiting_for_payment';

    /**
     * Automaticky zrušeno kvůli nezaplacení.
     * @var string
     */
    public const CANCELED_NOT_PAID = 'canceled_not_paid';

    /**
     * Zrušeno.
     * @var string
     */
    public const CANCELED = 'canceled';

    /**
     * Zaplaceno.
     * @var string
     */
    public const PAID = 'paid';

    /**
     * Zaplaceno (zdarma).
     * @var string
     */
    public const PAID_FREE = 'paid_free';
}
