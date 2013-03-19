<?php
/**
 * Date: 19.2.13
 * Time: 9:42
 * Author: Michal Májský
 */

namespace SRS\Form\Evidence;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

class AboutForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);

        $this->addHidden('id');
        $this->addTextArea('about', 'O mně');
        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn');
        $this->onSuccess[] = callback($this, 'submitted');
    }

    public function submitted()
    {
        $values = $this->getValues();
        $user = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($values['id']);
        $user->setProperties($values, $this->presenter->context->database);
        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('O mně uloženo', 'success');
        $this->presenter->redirect('this');
    }
}
