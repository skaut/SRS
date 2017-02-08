<?php

namespace App\WebModule\Components;

use Nette\Application\UI\Control;

class UsersContentControl extends Control
{
    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/users_content.latte');

        $template->heading = $content->getHeading();
        //$template->text = $content->getText();

        $template->render();
    }
}