<?php

namespace App\WebModule\Forms;


/**
 * Factory komponenty s formulářem pro zadání doplňujících informací.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IAdditionalInformationFormFactory
{
    /**
     * Vytvoří komponentu.
     * @return AdditionalInformationForm
     */
    public function create();
}
