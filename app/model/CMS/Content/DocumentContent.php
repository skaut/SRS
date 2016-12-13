<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 */
class DocumentContent extends Content implements IContent
{
    protected $content = Content::DOCUMENT;

    protected $tags; //TODO

    /**
     * Vytaha si sva data z formulare PageForm
     * @param Form $form
     * @return void
     */
    public function setValuesFromPageForm(Form $form)
    {
        parent::setValuesFromPageForm($form);
    }

    /**
     * Prida do formulare prvky, ktere dany content pozaduje vcetne predvyplnenych defaultnich hodnot
     * @param Form $form
     * @return Form $form
     */
    public function addFormItems(Form $form)
    {
        parrent::addFormItems($form);
        return $form;
    }
}