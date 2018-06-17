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
     * @param Form $form
     */
    public function addContentForm(Form $form);

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     * @param Form $form
     * @param array $values
     */
    public function contentFormSucceeded(Form $form, array $values);
}
