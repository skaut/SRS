<?php

namespace App\WebModule\Components;

use Nette\Application\UI\Control;

class NewsContentControl extends Control
{
    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/NewsContentControl.latte');

        //$template->text = $content->getText();

        $template->render();
    }
}