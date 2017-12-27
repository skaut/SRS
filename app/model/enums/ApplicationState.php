<?php

namespace App\Model\Enums;


class ApplicationState
{
    /**
     * Čeká na platbu.
     */
    const WAITING_FOR_PAYMENT = "waiting_for_payment";

    /**
     * Automaticky zrušeno kvůli nezaplacení.
     */
    const CANCELED_NOT_PAID = "canceled_not_paid";

    /**
     * Zrušeno.
     */
    const CANCELED = "canceled";

    /**
     * Zaplaceno.
     */
    const PAID = "paid";

    /**
     * Zaplaceno (zdarma).
     */
    const PAID_FREE = "paid_free";
}
