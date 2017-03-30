<?php

namespace App\Model\User\CustomInputValue;


/**
 * Rozhraní hodnot vlastních polí přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface ICustomInputValue
{
    /**
     * Vrací hodnotu vlastního pole přihlášky.
     */
    public function getValue();

    /**
     * Nastavuje hodnotu vlastního pole přihlášky.
     * @param $value
     */
    public function setValue($value);
}
