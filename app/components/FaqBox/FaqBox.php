<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 19.1.13
 * Time: 10:37
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Components;

class FaqBox extends \Nette\Application\UI\Control
{

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/template.latte');

        $this->template->faq = $this->presenter->context->database->getRepository('\SRS\model\CMS\Faq')->findAllOrderedPublished();
        $template->render();
    }

//    public function createComponentAttendeeForm() {
//        $roles = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->findRegisterableNow();
//        return new \SRS\Form\AttendeeForm(null, null,\SRS\Form\EntityForm::getFormChoices($roles));
//    }

}