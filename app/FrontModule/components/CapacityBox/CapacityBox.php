<?php
namespace SRS\Components;

/**
 * Komponenta pro vypis kapacit
 */
class CapacityBox extends \Nette\Application\UI\Control
{

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/template.latte');

        $this->template->roles = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->findCapacityVisibleRoles();

        $template->render();
    }


}