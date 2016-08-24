<?php
namespace SRS\Components;

/**
 * Komponenta pro vypis uzivatelu
 */
class UserBox extends \Nette\Application\UI\Control
{

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/template.latte');

        $this->template->users = $this->presenter->context->database->getRepository('\SRS\Model\User')->findUsersInVisibleRoles();

        $template->render();
    }


}