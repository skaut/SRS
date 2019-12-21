<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

use App\AdminModule\Forms\BaseForm;
use stdClass;

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
    public function addContentForm(BaseForm $form) : BaseForm;

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     */
    public function contentFormSucceeded(BaseForm $form, stdClass $values) : void;
}
