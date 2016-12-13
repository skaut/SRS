<?php

namespace App\Model\CMS\Content;

use Nette\Application\UI\Form;

interface IContent
{
    /**
     * Prida do formulare prvky, ktere dany content pozaduje vcetne predvyplnenych defaultnich hodnot
     * @param Form $form
     * @return Form $form
     */
    public function addFormItems(Form $form);

    /**
     * Vytaha si sva data z formulare PageForm
     * @param Form $form
     * @return void
     */
    public function setValuesFromPageForm(Form $form);
}