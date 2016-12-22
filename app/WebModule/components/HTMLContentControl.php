<?php

namespace App\WebModule\Components;

use Nette\Application\UI\Control;

class HTMLContentControl extends Control
{
    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/HTMLContentControl.latte');

        //$template->text = $content->getText();

        $template->render();
    }
}