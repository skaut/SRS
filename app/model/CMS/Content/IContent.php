<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use Nette\Application\UI\Form;

/**
 * Rozhraní obsahů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IContent
{
    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(Form $form) : Form;

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     * @param array $values
     */
    public function contentFormSucceeded(Form $form, array $values) : void;
}
