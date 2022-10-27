<?php

declare(strict_types=1);

namespace App\Model\Enums;

class TroopApplicationState
{
    /**
     * Rozpracovaná přihláška před potvrzením.
     */
    public const DRAFT = 'draft';

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
}
