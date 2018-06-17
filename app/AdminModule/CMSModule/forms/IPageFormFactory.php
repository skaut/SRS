<?php
declare(strict_types=1);

namespace App\AdminModule\CMSModule\Forms;


/**
 * Factory komponenty s formulářem pro úpravu obsahu stránky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IPageFormFactory
{
    /**
     * Vytvoří komponentu.
     * @param $id
     * @param $area
     * @return mixed
     */
    public function create($id, $area);
}
