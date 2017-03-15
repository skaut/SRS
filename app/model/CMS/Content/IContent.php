<?php

namespace App\Model\CMS\Content;

use Nette\Application\UI\Form;


interface IContent
{
    public function addContentForm(Form $form);

    public function contentFormSucceeded(Form $form, \stdClass $values);
}